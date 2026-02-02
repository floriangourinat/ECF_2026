import { TestBed } from '@angular/core/testing';
import { AuthService } from './auth.service';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';

describe('AuthService', () => {
  let service: AuthService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers: [AuthService]
    });
    service = TestBed.inject(AuthService);
    httpMock = TestBed.inject(HttpTestingController);
    localStorage.clear();
  });

  afterEach(() => {
    httpMock.verify();
    localStorage.clear();
  });

  // Tests de base
  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should have null currentUserValue initially', () => {
    expect(service.currentUserValue).toBeNull();
  });

  // Test login
  it('should login successfully', () => {
    const mockResponse = {
      message: 'Connexion réussie',
      token: 'fake-jwt-token',
      user: {
        id: 1,
        email: 'test@test.com',
        role: 'admin',
        first_name: 'Test',
        last_name: 'User',
        username: 'testuser',
        must_change_password: false
      }
    };

    service.login('test@test.com', 'password123').subscribe(user => {
      expect(user).toBeTruthy();
      expect(user.email).toBe('test@test.com');
    });

    const req = httpMock.expectOne('http://localhost:8080/api/auth/login.php');
    expect(req.request.method).toBe('POST');
    req.flush(mockResponse);
  });

  // Test logout
  it('should logout and clear localStorage', () => {
    localStorage.setItem('currentUser', JSON.stringify({ id: 1, email: 'test@test.com' }));
    service.logout();
    expect(localStorage.getItem('currentUser')).toBeNull();
  });

  // Test isLoggedIn
  it('should return false for isLoggedIn when no user', () => {
    expect(service.isLoggedIn()).toBeFalse();
  });

  // Test register
  it('should register a new user', () => {
    const mockResponse = { message: 'Compte créé avec succès', user_id: 2 };
    const userData = {
      email: 'new@test.com',
      password: 'Password123!',
      first_name: 'New',
      last_name: 'User',
      username: 'newuser'
    };

    service.register(userData).subscribe(response => {
      expect(response.message).toBe('Compte créé avec succès');
    });

    const req = httpMock.expectOne('http://localhost:8080/api/auth/register.php');
    expect(req.request.method).toBe('POST');
    req.flush(mockResponse);
  });

  // Test forgot password
  it('should send forgot password request', () => {
    const mockResponse = { message: 'Email envoyé' };

    service.forgotPassword('test@test.com').subscribe(response => {
      expect(response.message).toBeTruthy();
    });

    const req = httpMock.expectOne('http://localhost:8080/api/auth/forgot-password.php');
    expect(req.request.method).toBe('POST');
    req.flush(mockResponse);
  });
});