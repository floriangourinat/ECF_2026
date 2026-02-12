import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { AuthService } from './auth.service';

describe('AuthService', () => {
  let service: AuthService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    localStorage.clear();
    TestBed.configureTestingModule({ imports: [HttpClientTestingModule], providers: [AuthService] });
    service = TestBed.inject(AuthService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => { httpMock.verify(); localStorage.clear(); });

  it('should be created', () => { expect(service).toBeTruthy(); });
  it('should return null when no user logged in', () => { expect(service.currentUserValue).toBeNull(); });
  it('should return false for isLoggedIn when no user', () => { expect(service.isLoggedIn()).toBeFalse(); });

  it('should login successfully with admin role', (done) => {
    const mock = { token: 'tok', user: { id: 1, email: 'admin@innovevents.com', role: 'admin', first_name: 'Chloé' } };
    service.login('admin@innovevents.com', 'Admin123!').subscribe(user => {
      expect(user.role).toBe('admin');
      expect(service.isLoggedIn()).toBeTrue();
      expect(localStorage.getItem('mobileUser')).toBeTruthy();
      done();
    });
    const req = httpMock.expectOne('/api/auth/login.php');
    expect(req.request.method).toBe('POST');
    req.flush(mock);
  });

  it('should REJECT employee login', (done) => {
    const mock = { token: 'tok', user: { id: 21, email: 'emp@test.com', role: 'employee' } };
    service.login('emp@test.com', 'pass').subscribe({
      next: () => fail('Should reject'),
      error: (e) => { expect(e.message).toContain('administrateurs'); expect(service.isLoggedIn()).toBeFalse(); done(); }
    });
    httpMock.expectOne('/api/auth/login.php').flush(mock);
  });

  it('should REJECT client login', (done) => {
    const mock = { token: 'tok', user: { id: 14, email: 'cli@test.com', role: 'client' } };
    service.login('cli@test.com', 'pass').subscribe({
      next: () => fail('Should reject'),
      error: (e) => { expect(e.message).toContain('administrateurs'); expect(localStorage.getItem('mobileUser')).toBeNull(); done(); }
    });
    httpMock.expectOne('/api/auth/login.php').flush(mock);
  });

  it('should handle server error', (done) => {
    service.login('x@x.com', 'x').subscribe({
      next: () => fail('Should fail'),
      error: (e) => { expect(e).toBeTruthy(); expect(service.isLoggedIn()).toBeFalse(); done(); }
    });
    httpMock.expectOne('/api/auth/login.php').flush({ message: 'bad' }, { status: 401, statusText: 'Unauthorized' });
  });

  it('should logout and clear data', (done) => {
    const mock = { token: 'tok', user: { id: 1, email: 'a@a.com', role: 'admin' } };
    service.login('a@a.com', 'p').subscribe(() => {
      service.logout();
      expect(service.currentUserValue).toBeNull();
      expect(service.isLoggedIn()).toBeFalse();
      expect(localStorage.getItem('mobileUser')).toBeNull();
      done();
    });
    httpMock.expectOne('/api/auth/login.php').flush(mock);
  });

  it('should store token in localStorage', (done) => {
    const mock = { token: 'secure-tok', user: { id: 1, email: 'a@a.com', role: 'admin' } };
    service.login('a@a.com', 'p').subscribe(() => {
      const stored = JSON.parse(localStorage.getItem('mobileUser')!);
      expect(stored.token).toBe('secure-tok');
      expect(stored.role).toBe('admin');
      done();
    });
    httpMock.expectOne('/api/auth/login.php').flush(mock);
  });

  it('should send correct POST body', (done) => {
    service.login('test@t.com', 'myPass').subscribe({ error: () => done() });
    const req = httpMock.expectOne('/api/auth/login.php');
    expect(req.request.body).toEqual({ email: 'test@t.com', password: 'myPass' });
    req.flush({}, { status: 401, statusText: 'Unauthorized' });
  });
});
