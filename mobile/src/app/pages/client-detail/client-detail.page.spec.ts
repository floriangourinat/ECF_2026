import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { ActivatedRoute } from '@angular/router';
import { RouterTestingModule } from '@angular/router/testing';

import { ClientDetailPage } from './client-detail.page';

describe('ClientDetailPage', () => {
  let component: ClientDetailPage;
  let fixture: ComponentFixture<ClientDetailPage>;
  let httpMock: HttpTestingController;

  const mockClient = {
    success: true,
    data: {
      client: {
        id: 10,
        first_name: 'Jean',
        last_name: 'Dupont',
        email: 'jean@tech.com',
        phone: '0612345678',
        address: '15 rue de la Paix, Paris',
        company_name: 'TechCorp'
      },
      events: [
        {
          id: 1,
          name: 'Séminaire',
          start_date: '2026-04-15',
          location: 'Paris'
        }
      ]
    }
  };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HttpClientTestingModule, RouterTestingModule, ClientDetailPage],
      providers: [
        {
          provide: ActivatedRoute,
          useValue: {
            snapshot: {
              paramMap: {
                get: () => '10'
              }
            }
          }
        }
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(ClientDetailPage);
    component = fixture.componentInstance;

    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should start loading', () => {
    expect(component.loading).toBeTrue();
    expect(component.client).toBeNull();
    expect(component.events).toEqual([]);
  });

  it('should load client with nested client and events data', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/clients/read_one.php?id=10').flush(mockClient);

    expect(component.loading).toBeFalse();
    expect(component.client.first_name).toBe('Jean');
    expect(component.client.company_name).toBe('TechCorp');
    expect(component.events.length).toBe(1);
  });

  it('should load client when API returns direct data object', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/clients/read_one.php?id=10').flush({
      success: true,
      data: {
        id: 11,
        first_name: 'Alice',
        last_name: 'Martin',
        email: 'alice@innovevents.com',
        phone: '',
        address: '',
        company_name: ''
      }
    });

    expect(component.loading).toBeFalse();
    expect(component.client.id).toBe(11);
    expect(component.client.first_name).toBe('Alice');
    expect(component.events).toEqual([]);
  });

  it('should handle API error', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/clients/read_one.php?id=10').flush(
      'Erreur serveur',
      { status: 500, statusText: 'Error' }
    );

    expect(component.loading).toBeFalse();
    expect(component.client).toBeNull();
    expect(component.events).toEqual([]);
  });

  it('should generate encoded Google Maps URL', () => {
    fixture.detectChanges();

    httpMock.expectOne('/api/clients/read_one.php?id=10').flush(mockClient);

    const mapsUrl = component.getMapsUrl();

    expect(mapsUrl).toContain('google.com/maps');
    expect(mapsUrl).toContain('15%20rue');
    expect(mapsUrl).toContain('Paris');
  });

  it('should generate Google Maps URL with empty query when address is missing', () => {
    component.client = {
      id: 10,
      first_name: 'Jean',
      last_name: 'Dupont',
      address: ''
    };

    const mapsUrl = component.getMapsUrl();

    expect(mapsUrl).toBe('https://www.google.com/maps/search/?api=1&query=');
  });

  it('should format client event date', () => {
    expect(component.formatDate('2026-04-15')).toContain('2026');
    expect(component.formatDate('')).toBe('-');
    expect(component.formatDate(null as any)).toBe('-');
  });
});