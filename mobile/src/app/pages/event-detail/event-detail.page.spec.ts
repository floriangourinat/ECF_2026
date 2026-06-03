import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { ActivatedRoute, Router } from '@angular/router';
import { RouterTestingModule } from '@angular/router/testing';

import { EventDetailPage } from './event-detail.page';
import { AuthService } from '../../services/auth.service';

describe('EventDetailPage', () => {
  let component: EventDetailPage;
  let fixture: ComponentFixture<EventDetailPage>;
  let httpMock: HttpTestingController;
  let router: Router;
  let navigateSpy: jasmine.Spy;

  const authServiceMock = {
    currentUserValue: {
      id: 1,
      email: 'admin@innovevents.com',
      role: 'admin'
    }
  };

  /**
   * Génère un jeu de données neuf pour chaque test.
   * Cela évite qu'un test modifie le tableau des notes utilisé par un autre test.
   */
  const createMockDetail = () => ({
    success: true,
    data: {
      event: {
        id: 1,
        name: 'Séminaire',
        status: 'accepted',
        start_date: '2026-04-15',
        end_date: '2026-04-16',
        location: 'Paris',
        client_id: 10,
        client_company: 'TechCorp',
        description: 'Séminaire annuel'
      },
      notes: [
        {
          id: 1,
          content: 'Note test',
          first_name: 'Chloé',
          last_name: 'Dubois',
          author_id: 1,
          created_at: '2026-02-10'
        }
      ]
    }
  });

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HttpClientTestingModule, RouterTestingModule, EventDetailPage],
      providers: [
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              paramMap: {
                get: () => '1'
              }
            }
          }
        },
        {
          provide: AuthService,
          useValue: authServiceMock
        }
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(EventDetailPage);
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

  it('should start loading', () => {
    expect(component.loading).toBeTrue();
  });

  it('should have default note form state', () => {
    expect(component.newNote).toBe('');
    expect(component.addingNote).toBeFalse();
    expect(component.showNoteForm).toBeFalse();
  });

  it('should format dates', () => {
    expect(component.formatDate('2026-04-15')).toContain('avril');
    expect(component.formatDate('')).toBe('-');
    expect(component.formatDate(null as any)).toBe('-');
  });

  it('should format date time', () => {
    expect(component.formatDateTime('2026-02-10')).toContain('10');
    expect(component.formatDateTime('')).toBe('-');
    expect(component.formatDateTime(null as any)).toBe('-');
  });

  it('should return correct status colors', () => {
    expect(component.getStatusColor('draft')).toBe('medium');
    expect(component.getStatusColor('client_review')).toBe('warning');
    expect(component.getStatusColor('accepted')).toBe('primary');
    expect(component.getStatusColor('in_progress')).toBe('success');
    expect(component.getStatusColor('completed')).toBe('success');
    expect(component.getStatusColor('cancelled')).toBe('danger');
  });

  it('should return default status color for unknown status', () => {
    expect(component.getStatusColor('unknown')).toBe('medium');
  });

  it('should load event detail', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/events/read_detail.php?id=1').flush(createMockDetail());

    expect(component.loading).toBeFalse();
    expect(component.event.name).toBe('Séminaire');
    expect(component.notes.length).toBe(1);
  });

  it('should load event detail without notes', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/events/read_detail.php?id=1').flush({
      success: true,
      data: {
        event: {
          id: 1,
          name: 'Séminaire',
          status: 'accepted',
          start_date: '2026-04-15',
          end_date: '2026-04-16',
          client_id: 10
        }
      }
    });

    expect(component.loading).toBeFalse();
    expect(component.event.name).toBe('Séminaire');
    expect(component.notes).toEqual([]);
  });

  it('should handle API error while loading event detail', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/events/read_detail.php?id=1').flush(
      'Erreur serveur',
      { status: 500, statusText: 'Error' }
    );

    expect(component.loading).toBeFalse();
    expect(component.event).toBeNull();
    expect(component.notes).toEqual([]);
  });

  it('should navigate to client detail when client id exists', () => {
    component.event = {
      id: 1,
      client_id: 10
    };

    component.openClient();

    expect(navigateSpy).toHaveBeenCalledWith(['/client', 10]);
  });

  it('should not navigate to client detail when client id is missing', () => {
    component.event = {
      id: 1,
      client_id: null
    };

    component.openClient();

    expect(navigateSpy).not.toHaveBeenCalled();
  });

  it('should not navigate to client detail when event is not loaded', () => {
    component.event = null;

    component.openClient();

    expect(navigateSpy).not.toHaveBeenCalled();
  });

  it('should toggle note form and clear note when closing it', () => {
    component.toggleNoteForm();

    expect(component.showNoteForm).toBeTrue();

    component.newNote = 'Brouillon de note';
    component.toggleNoteForm();

    expect(component.showNoteForm).toBeFalse();
    expect(component.newNote).toBe('');
  });

  it('should not add empty note', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/events/read_detail.php?id=1').flush(createMockDetail());

    component.newNote = '   ';
    component.addNote();

    expect(component.addingNote).toBeFalse();
    expect(component.notes.length).toBe(1);
  });

  it('should add a note successfully', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/events/read_detail.php?id=1').flush(createMockDetail());

    const notesBefore = component.notes.length;

    component.newNote = 'Ma note';
    component.showNoteForm = true;
    component.addNote();

    const req = httpMock.expectOne('/api/notes/create.php');

    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({
      event_id: 1,
      author_id: 1,
      content: 'Ma note'
    });

    req.flush({
      success: true,
      data: {
        id: 99,
        content: 'Ma note',
        first_name: 'Chloé',
        last_name: 'D',
        created_at: '2026-02-12'
      }
    });

    expect(component.addingNote).toBeFalse();
    expect(component.notes.length).toBe(notesBefore + 1);
    expect(component.notes[0].content).toBe('Ma note');
    expect(component.newNote).toBe('');
    expect(component.showNoteForm).toBeFalse();
  });

  it('should keep note text when API returns success=false', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/events/read_detail.php?id=1').flush(createMockDetail());

    const notesBefore = component.notes.length;

    component.newNote = 'Note non enregistrée';
    component.showNoteForm = true;
    component.addNote();

    httpMock.expectOne('/api/notes/create.php').flush({
      success: false,
      message: 'Erreur de validation'
    });

    expect(component.addingNote).toBeFalse();
    expect(component.notes.length).toBe(notesBefore);
    expect(component.newNote).toBe('Note non enregistrée');
    expect(component.showNoteForm).toBeTrue();
  });

  it('should handle API error while adding note', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/events/read_detail.php?id=1').flush(createMockDetail());

    const notesBefore = component.notes.length;

    component.newNote = 'Note en erreur';
    component.showNoteForm = true;
    component.addNote();

    httpMock.expectOne('/api/notes/create.php').flush(
      'Erreur serveur',
      { status: 500, statusText: 'Error' }
    );

    expect(component.addingNote).toBeFalse();
    expect(component.notes.length).toBe(notesBefore);
    expect(component.newNote).toBe('Note en erreur');
    expect(component.showNoteForm).toBeTrue();
  });
});