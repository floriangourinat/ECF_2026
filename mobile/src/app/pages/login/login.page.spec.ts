import { ComponentFixture, TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { RouterTestingModule } from '@angular/router/testing';
import { of, throwError } from 'rxjs';

import { LoginPage } from './login.page';
import { AuthService } from '../../services/auth.service';

describe('LoginPage', () => {
  let component: LoginPage;
  let fixture: ComponentFixture<LoginPage>;
  let authService: jasmine.SpyObj<AuthService>;
  let router: Router;
  let navigateSpy: jasmine.Spy;

  beforeEach(async () => {
    localStorage.clear();

    authService = jasmine.createSpyObj<AuthService>('AuthService', ['isLoggedIn', 'login']);
    authService.isLoggedIn.and.returnValue(false);

    await TestBed.configureTestingModule({
      imports: [RouterTestingModule, LoginPage],
      providers: [
        { provide: AuthService, useValue: authService }
      ]
    }).compileComponents();

    router = TestBed.inject(Router);
    navigateSpy = spyOn(router, 'navigate').and.returnValue(Promise.resolve(true));

    fixture = TestBed.createComponent(LoginPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  afterEach(() => {
    localStorage.clear();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should init with empty fields', () => {
    expect(component.email).toBe('');
    expect(component.password).toBe('');
    expect(component.loading).toBeFalse();
    expect(component.error).toBe('');
  });

  it('should redirect when a user is already logged in', () => {
    navigateSpy.calls.reset();
    authService.isLoggedIn.and.returnValue(true);

    const connectedFixture = TestBed.createComponent(LoginPage);
    connectedFixture.detectChanges();

    expect(navigateSpy).toHaveBeenCalledWith(['/tabs']);

    connectedFixture.destroy();
  });

  it('should show an error when fields are empty', () => {
    component.onLogin();

    expect(component.error).toBe('Veuillez remplir tous les champs');
    expect(component.loading).toBeFalse();
    expect(authService.login).not.toHaveBeenCalled();
  });

  it('should show an error when only email is filled', () => {
    component.email = 'admin@innovevents.com';

    component.onLogin();

    expect(component.error).toBe('Veuillez remplir tous les champs');
    expect(component.loading).toBeFalse();
    expect(authService.login).not.toHaveBeenCalled();
  });

  it('should show an error when only password is filled', () => {
    component.password = 'Admin123!';

    component.onLogin();

    expect(component.error).toBe('Veuillez remplir tous les champs');
    expect(component.loading).toBeFalse();
    expect(authService.login).not.toHaveBeenCalled();
  });

  it('should login and navigate to tabs when credentials are valid', () => {
    const connectedUser = {
      id: 1,
      email: 'admin@innovevents.com',
      role: 'admin',
      token: 'secure-token'
    };

    authService.login.and.returnValue(of(connectedUser));

    component.email = 'admin@innovevents.com';
    component.password = 'Admin123!';

    component.onLogin();

    expect(component.error).toBe('');
    expect(component.loading).toBeTrue();
    expect(authService.login).toHaveBeenCalledWith('admin@innovevents.com', 'Admin123!');
    expect(navigateSpy).toHaveBeenCalledWith(['/tabs']);
  });

  it('should display API error message when login fails with server message', () => {
    authService.login.and.returnValue(
      throwError(() => ({ error: { message: 'Compte désactivé' } }))
    );

    component.email = 'admin@innovevents.com';
    component.password = 'wrong-password';

    component.onLogin();

    expect(component.loading).toBeFalse();
    expect(component.error).toBe('Compte désactivé');
  });

  it('should display thrown error message when login fails with Error object', () => {
    authService.login.and.returnValue(
      throwError(() => new Error('Accès réservé aux administrateurs'))
    );

    component.email = 'client@innovevents.com';
    component.password = 'Client123!';

    component.onLogin();

    expect(component.loading).toBeFalse();
    expect(component.error).toBe('Accès réservé aux administrateurs');
  });

  it('should display default error message when login fails without details', () => {
    authService.login.and.returnValue(
      throwError(() => ({}))
    );

    component.email = 'admin@innovevents.com';
    component.password = 'wrong-password';

    component.onLogin();

    expect(component.loading).toBeFalse();
    expect(component.error).toBe('Identifiants incorrects');
  });
});