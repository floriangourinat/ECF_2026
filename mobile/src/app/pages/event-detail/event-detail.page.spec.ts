import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { ActivatedRoute } from '@angular/router';
import { EventDetailPage } from './event-detail.page';
import { AuthService } from '../../services/auth.service';

describe('EventDetailPage', () => {
  let component: EventDetailPage;
  let fixture: ComponentFixture<EventDetailPage>;
  let httpMock: HttpTestingController;

  const mockDetail = { success: true, data: {
    event: { id: 1, name: 'Séminaire', status: 'accepted', start_date: '2026-04-15', end_date: '2026-04-16', location: 'Paris', client_id: 10, client_company: 'TechCorp' },
    notes: [{ id: 1, content: 'Note test', first_name: 'Chloé', last_name: 'Dubois', author_id: 1, created_at: '2026-02-10' }]
  }};

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HttpClientTestingModule, RouterTestingModule, EventDetailPage],
      providers: [AuthService, { provide: ActivatedRoute, useValue: { snapshot: { paramMap: { get: () => '1' } } } }]
    }).compileComponents();
    fixture = TestBed.createComponent(EventDetailPage);
    component = fixture.componentInstance;
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => { httpMock.verify(); });

  it('should create', () => { expect(component).toBeTruthy(); });
  it('should start loading', () => { expect(component.loading).toBeTrue(); });
  it('should format dates', () => { expect(component.formatDate('2026-04-15')).toContain('avril'); expect(component.formatDate('')).toBe('-'); });

  it('should load event detail', () => {
    fixture.detectChanges();
    const reqs = httpMock.match('/api/events/read_detail.php?id=1');
    reqs.forEach(req => req.flush(mockDetail));
    expect(component.event.name).toBe('Séminaire');
    expect(component.notes.length).toBeGreaterThanOrEqual(1);
  });

  it('should not add empty note', () => {
    fixture.detectChanges();
    httpMock.match('/api/events/read_detail.php?id=1').forEach(r => r.flush(mockDetail));
    component.newNote = '   ';
    component.addNote();
    expect(component.addingNote).toBeFalse();
  });

  it('should add a note', () => {
    fixture.detectChanges();
    httpMock.match('/api/events/read_detail.php?id=1').forEach(r => r.flush(mockDetail));
    const notesBefore = component.notes.length;
    component.newNote = 'Ma note';
    component.addNote();
    const req = httpMock.expectOne('/api/notes/create.php');
    expect(req.request.body.content).toBe('Ma note');
    req.flush({ success: true, data: { id: 99, content: 'Ma note', first_name: 'Chloé', last_name: 'D', created_at: '2026-02-12' } });
    expect(component.notes.length).toBe(notesBefore + 1);
    expect(component.newNote).toBe('');
  });
});