import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { ActivatedRoute } from '@angular/router';
import { ClientDetailPage } from './client-detail.page';

describe('ClientDetailPage', () => {
  let component: ClientDetailPage;
  let fixture: ComponentFixture<ClientDetailPage>;
  let httpMock: HttpTestingController;

  const mockClient = { success: true, data: {
    client: { id: 10, first_name: 'Jean', last_name: 'Dupont', email: 'jean@tech.com', phone: '0612345678', address: '15 rue de la Paix, Paris', company_name: 'TechCorp' },
    events: [{ id: 1, name: 'Séminaire', start_date: '2026-04-15', location: 'Paris' }]
  }};

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HttpClientTestingModule, RouterTestingModule, ClientDetailPage],
      providers: [{ provide: ActivatedRoute, useValue: { snapshot: { paramMap: { get: () => '10' } } } }]
    }).compileComponents();
    fixture = TestBed.createComponent(ClientDetailPage);
    component = fixture.componentInstance;
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => { httpMock.verify(); });

  it('should create', () => { expect(component).toBeTruthy(); });
  it('should start loading', () => { expect(component.loading).toBeTrue(); });

  it('should load client', () => {
    fixture.detectChanges();
    httpMock.expectOne('/api/clients/read_detail.php?id=10').flush(mockClient);
    expect(component.client.first_name).toBe('Jean');
    expect(component.events.length).toBe(1);
  });

  it('should generate maps URL', () => {
    fixture.detectChanges();
    httpMock.expectOne('/api/clients/read_detail.php?id=10').flush(mockClient);
    expect(component.getMapsUrl()).toContain('google.com/maps');
    expect(component.getMapsUrl()).toContain('15%20rue');
  });

  it('should handle API error', () => {
    fixture.detectChanges();
    httpMock.expectOne('/api/clients/read_detail.php?id=10').flush('err', { status: 500, statusText: 'Error' });
    expect(component.loading).toBeFalse();
    expect(component.client).toBeNull();
  });
});
