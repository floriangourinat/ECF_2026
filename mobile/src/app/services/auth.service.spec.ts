import { TestBed } from '@angular/core/testing';
import { HttpClient } from '@angular/common/http';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';

import { AuthService } from './auth.service';

describe('AuthService', () => {
  let service: AuthService;
  let httpMock: HttpTestingController;
  let httpClient: HttpClient;

  beforeEach(() => {
    localStorage.clear();

    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [AuthService]
    });

    service = TestBed.inject(AuthService);
    httpMock = TestBed.inject(HttpTestingController);
    httpClient = TestBed.inject(HttpClient);
  });

  afterEach(() => {
    httpMock.verify();
    localStorage.clear();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should return null when no user is logged in', () => {
    expect(service.currentUserValue).toBeNull();
  });

  it('should return false for isLoggedIn when no user is stored', () => {
    expect(service.isLoggedIn()).toBeFalse();
  });

  it('should restore current user from localStorage at service construction', () => {
    const storedUser = {
      id: 1,
      email: 'admin@innovevents.com',
      role: 'admin',
      token: 'stored-token'
    };

    localStorage.setItem('mobileUser', JSON.stringify(storedUser));

    const restoredService = new AuthService(httpClient);

    expect(restoredService.currentUserValue).toEqual(storedUser);
    expect(restoredService.isLoggedIn()).toBeTrue();
  });

  it('should login successfully with admin role', (done) => {
    const mockResponse = {
      token: 'secure-token',
      user: {
        id: 1,
        email: 'admin@innovevents.com',
        role: 'admin',
        first_name: 'Chloé'
      }
    };

    service.login('admin@innovevents.com', 'Admin123!').subscribe((user) => {
      expect(user.role).toBe('admin');
      expect(user.token).toBe('secure-token');
      expect(service.isLoggedIn()).toBeTrue();
      expect(localStorage.getItem('mobileUser')).toBeTruthy();
      done();
    });

    const req = httpMock.expectOne('/api/auth/login.php');

    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({
      email: 'admin@innovevents.com',
      password: 'Admin123!'
    });

    req.flush(mockResponse);
  });

  it('should emit current user after successful login', (done) => {
    const emissions: any[] = [];
    const subscription = service.currentUser.subscribe((user) => emissions.push(user));

    const mockResponse = {
      token: 'secure-token',
      user: {
        id: 1,
        email: 'admin@innovevents.com',
        role: 'admin'
      }
    };

    service.login('admin@innovevents.com', 'Admin123!').subscribe(() => {
      expect(emissions[0]).toBeNull();
      expect(emissions[emissions.length - 1].role).toBe('admin');
      expect(emissions[emissions.length - 1].token).toBe('secure-token');
      subscription.unsubscribe();
      done();
    });

    httpMock.expectOne('/api/auth/login.php').flush(mockResponse);
  });

  it('should reject employee login', (done) => {
    const mockResponse = {
      token: 'employee-token',
      user: {
        id: 21,
        email: 'employee@innovevents.com',
        role: 'employee'
      }
    };

    service.login('employee@innovevents.com', 'Employee123!').subscribe({
      next: () => fail('Employee login should be rejected on mobile application'),
      error: (error) => {
        expect(error.message).toContain('administrateurs');
        expect(service.isLoggedIn()).toBeFalse();
        expect(localStorage.getItem('mobileUser')).toBeNull();
        done();
      }
    });

    httpMock.expectOne('/api/auth/login.php').flush(mockResponse);
  });

  it('should reject client login', (done) => {
    const mockResponse = {
      token: 'client-token',
      user: {
        id: 14,
        email: 'client@innovevents.com',
        role: 'client'
      }
    };

    service.login('client@innovevents.com', 'Client123!').subscribe({
      next: () => fail('Client login should be rejected on mobile application'),
      error: (error) => {
        expect(error.message).toContain('administrateurs');
        expect(service.isLoggedIn()).toBeFalse();
        expect(localStorage.getItem('mobileUser')).toBeNull();
        done();
      }
    });

    httpMock.expectOne('/api/auth/login.php').flush(mockResponse);
  });

  it('should reject a successful HTTP response without user object and use API message', (done) => {
    service.login('admin@innovevents.com', 'Admin123!').subscribe({
      next: () => fail('Login should fail when API response has no user object'),
      error: (error) => {
        expect(error.message).toBe('Réponse invalide');
        expect(service.isLoggedIn()).toBeFalse();
        done();
      }
    });

    httpMock.expectOne('/api/auth/login.php').flush({
      success: false,
      message: 'Réponse invalide'
    });
  });

  it('should reject a successful HTTP response without user object and use default message', (done) => {
    service.login('admin@innovevents.com', 'Admin123!').subscribe({
      next: () => fail('Login should fail when API response is incomplete'),
      error: (error) => {
        expect(error.message).toBe('Erreur de connexion');
        expect(service.isLoggedIn()).toBeFalse();
        done();
      }
    });

    httpMock.expectOne('/api/auth/login.php').flush({});
  });

  it('should handle server error', (done) => {
    service.login('admin@innovevents.com', 'wrong-password').subscribe({
      next: () => fail('Login should fail on HTTP error'),
      error: (error) => {
        expect(error).toBeTruthy();
        expect(service.isLoggedIn()).toBeFalse();
        done();
      }
    });

    httpMock.expectOne('/api/auth/login.php').flush(
      { message: 'Identifiants incorrects' },
      { status: 401, statusText: 'Unauthorized' }
    );
  });

  it('should logout and clear stored data', (done) => {
    const mockResponse = {
      token: 'secure-token',
      user: {
        id: 1,
        email: 'admin@innovevents.com',
        role: 'admin'
      }
    };

    service.login('admin@innovevents.com', 'Admin123!').subscribe(() => {
      service.logout();

      expect(service.currentUserValue).toBeNull();
      expect(service.isLoggedIn()).toBeFalse();
      expect(localStorage.getItem('mobileUser')).toBeNull();
      done();
    });

    httpMock.expectOne('/api/auth/login.php').flush(mockResponse);
  });

  it('should store token in localStorage', (done) => {
    const mockResponse = {
      token: 'secure-token',
      user: {
        id: 1,
        email: 'admin@innovevents.com',
        role: 'admin'
      }
    };

    service.login('admin@innovevents.com', 'Admin123!').subscribe(() => {
      const storedUser = JSON.parse(localStorage.getItem('mobileUser')!);

      expect(storedUser.token).toBe('secure-token');
      expect(storedUser.role).toBe('admin');
      done();
    });

    httpMock.expectOne('/api/auth/login.php').flush(mockResponse);
  });

  it('should send correct POST body', (done) => {
    service.login('test@innovevents.com', 'myPass').subscribe({
      error: () => done()
    });

    const req = httpMock.expectOne('/api/auth/login.php');

    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({
      email: 'test@innovevents.com',
      password: 'myPass'
    });

    req.flush(
      { message: 'Identifiants incorrects' },
      { status: 401, statusText: 'Unauthorized' }
    );
  });
});