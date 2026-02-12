import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { EventsPage } from './events.page';
import { AuthService } from '../../services/auth.service';

describe('EventsPage', () => {
  let component: EventsPage;
  let fixture: ComponentFixture<EventsPage>;
  let httpMock: HttpTestingController;

  const mockEvents = { success: true, data: [
    { id: 1, name: 'Séminaire Tech', status: 'accepted', start_date: '2026-04-15', location: 'Paris', client_company: 'TechCorp' },
    { id: 2, name: 'Conférence', status: 'in_progress', start_date: '2026-03-20', location: 'Lyon', client_company: 'MarketPlus' },
    { id: 3, name: 'Gala', status: 'completed', start_date: '2026-01-10', client_company: 'X' },
    { id: 4, name: 'Annulé', status: 'cancelled', start_date: '2026-05-01', client_company: 'Y' }
  ]};

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HttpClientTestingModule, RouterTestingModule, EventsPage],
      providers: [AuthService]
    }).compileComponents();
    fixture = TestBed.createComponent(EventsPage);
    component = fixture.componentInstance;
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => { httpMock.verify(); });

  it('should create', () => { expect(component).toBeTruthy(); });
  it('should init with loading=true', () => { expect(component.loading).toBeTrue(); expect(component.events).toEqual([]); });
  it('should have status labels', () => { expect(component.statusLabels['draft']).toBe('Brouillon'); });
  it('should return correct status color', () => { expect(component.getStatusColor('in_progress')).toBe('success'); expect(component.getStatusColor('cancelled')).toBe('danger'); });
  it('should format date', () => { expect(component.formatDate('2026-04-15')).toContain('2026'); expect(component.formatDate('')).toBe('-'); });

  it('should load and filter events', () => {
    fixture.detectChanges();
    httpMock.expectOne('/api/events/read_all.php').flush(mockEvents);
    expect(component.loading).toBeFalse();
    expect(component.events.length).toBe(2);
    expect(component.events[0].name).toBe('Conférence');
  });

  it('should sort by date ascending', () => {
    fixture.detectChanges();
    httpMock.expectOne('/api/events/read_all.php').flush(mockEvents);
    const dates = component.events.map((e: any) => new Date(e.start_date).getTime());
    expect(dates[0]).toBeLessThanOrEqual(dates[1]);
  });

  it('should handle empty response', () => {
    fixture.detectChanges();
    httpMock.expectOne('/api/events/read_all.php').flush({ success: true, data: [] });
    expect(component.events.length).toBe(0);
  });

  it('should handle API error', () => {
    fixture.detectChanges();
    httpMock.expectOne('/api/events/read_all.php').flush('err', { status: 500, statusText: 'Error' });
    expect(component.loading).toBeFalse();
  });
});
