import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { Router } from '@angular/router';
import { RouterTestingModule } from '@angular/router/testing';

import { EventsPage } from './events.page';
import { AuthService } from '../../services/auth.service';

describe('EventsPage', () => {
  let component: EventsPage;
  let fixture: ComponentFixture<EventsPage>;
  let httpMock: HttpTestingController;
  let router: Router;
  let navigateSpy: jasmine.Spy;

  const mockEvents = {
    success: true,
    data: [
      {
        id: 1,
        name: 'Séminaire Tech',
        status: 'accepted',
        start_date: '2026-04-15',
        location: 'Paris',
        client_company: 'TechCorp'
      },
      {
        id: 2,
        name: 'Conférence',
        status: 'in_progress',
        start_date: '2026-03-20',
        location: 'Lyon',
        client_company: 'MarketPlus'
      },
      {
        id: 3,
        name: 'Gala',
        status: 'completed',
        start_date: '2026-01-10',
        location: 'Bordeaux',
        client_company: 'X'
      },
      {
        id: 4,
        name: 'Annulé',
        status: 'cancelled',
        start_date: '2026-05-01',
        location: 'Toulouse',
        client_company: 'Y'
      }
    ]
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HttpClientTestingModule, RouterTestingModule, EventsPage],
      providers: [AuthService]
    }).compileComponents();

    fixture = TestBed.createComponent(EventsPage);
    component = fixture.componentInstance;

    httpMock = TestBed.inject(HttpTestingController);
    router = TestBed.inject(Router);
    navigateSpy = spyOn(router, 'navigate').and.returnValue(Promise.resolve(true));
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should init with loading=true and empty events list', () => {
    expect(component.loading).toBeTrue();
    expect(component.events).toEqual([]);
  });

  it('should have status labels', () => {
    expect(component.statusLabels['draft']).toBe('Brouillon');
    expect(component.statusLabels['client_review']).toBe('En attente');
    expect(component.statusLabels['accepted']).toBe('Accepté');
    expect(component.statusLabels['in_progress']).toBe('En cours');
    expect(component.statusLabels['completed']).toBe('Terminé');
    expect(component.statusLabels['cancelled']).toBe('Annulé');
  });

  it('should return correct status colors', () => {
    expect(component.getStatusColor('draft')).toBe('medium');
    expect(component.getStatusColor('client_review')).toBe('warning');
    expect(component.getStatusColor('accepted')).toBe('primary');
    expect(component.getStatusColor('in_progress')).toBe('success');
    expect(component.getStatusColor('completed')).toBe('success');
    expect(component.getStatusColor('cancelled')).toBe('danger');
  });

  it('should return default color for unknown status', () => {
    expect(component.getStatusColor('unknown')).toBe('medium');
  });

  it('should format date', () => {
    expect(component.formatDate('2026-04-15')).toContain('2026');
    expect(component.formatDate('')).toBe('-');
    expect(component.formatDate(null as any)).toBe('-');
  });

  it('should load, filter and sort events', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/events/read_all.php').flush(mockEvents);

    expect(component.loading).toBeFalse();
    expect(component.events.length).toBe(2);
    expect(component.events[0].name).toBe('Conférence');
    expect(component.events[1].name).toBe('Séminaire Tech');
  });

  it('should render event list after loading', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/events/read_all.php').flush(mockEvents);
    fixture.detectChanges();

    const textContent = fixture.nativeElement.textContent;

    expect(textContent).toContain('Conférence');
    expect(textContent).toContain('Séminaire Tech');
    expect(textContent).not.toContain('Gala');
    expect(textContent).not.toContain('Annulé');
  });

  it('should sort events by ascending start date', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/events/read_all.php').flush(mockEvents);

    const dates = component.events.map((event: any) => new Date(event.start_date).getTime());

    expect(dates[0]).toBeLessThanOrEqual(dates[1]);
  });

  it('should handle empty response', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/events/read_all.php').flush({
      success: true,
      data: []
    });

    expect(component.loading).toBeFalse();
    expect(component.events.length).toBe(0);
  });

  it('should handle response without data property', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/events/read_all.php').flush({
      success: true
    });

    expect(component.loading).toBeFalse();
    expect(component.events).toEqual([]);
  });

  it('should render empty state when there is no event', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/events/read_all.php').flush({
      success: true,
      data: []
    });

    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Aucun événement à venir');
  });

  it('should handle API error', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/events/read_all.php').flush(
      'Erreur serveur',
      { status: 500, statusText: 'Error' }
    );

    expect(component.loading).toBeFalse();
    expect(component.events).toEqual([]);
  });

  it('should navigate to event detail', () => {
    component.openEvent({ id: 12 });

    expect(navigateSpy).toHaveBeenCalledWith(['/event', 12]);
  });

  it('should refresh events and complete refresher animation', fakeAsync(() => {
    const completeSpy = jasmine.createSpy('complete');
    const refreshEvent = {
      target: {
        complete: completeSpy
      }
    };

    spyOn(component, 'loadEvents').and.callFake(() => undefined);

    component.doRefresh(refreshEvent);

    expect(component.loadEvents).toHaveBeenCalled();

    tick(1000);

    expect(completeSpy).toHaveBeenCalled();
  }));
});