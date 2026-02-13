import { ComponentFixture, TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { RouterTestingModule } from '@angular/router/testing';
import { HeaderComponent } from './header';
import { AuthService } from '../../_services/auth.service';

class MockAuthService {
  private loggedIn = false;

  isLoggedIn(): boolean {
    return this.loggedIn;
  }

  setLoggedIn(value: boolean): void {
    this.loggedIn = value;
  }

  logout(): void {
    this.loggedIn = false;
  }
}

describe('HeaderComponent', () => {
  let component: HeaderComponent;
  let fixture: ComponentFixture<HeaderComponent>;
  let authService: MockAuthService;
  let router: Router;

  beforeEach(async () => {
    authService = new MockAuthService();

    await TestBed.configureTestingModule({
      imports: [HeaderComponent, RouterTestingModule],
      providers: [{ provide: AuthService, useValue: authService }]
    }).compileComponents();

    fixture = TestBed.createComponent(HeaderComponent);
    component = fixture.componentInstance;
    router = TestBed.inject(Router);
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should display required public menu links', () => {
    const navLinks = fixture.nativeElement.querySelectorAll('.nav .nav-link') as NodeListOf<HTMLAnchorElement>;
    const linksText = Array.from(navLinks).map((el: HTMLAnchorElement) => el.textContent?.trim());

    expect(linksText).toContain('Accueil');
    expect(linksText).toContain('Événements');
    expect(linksText).toContain('Avis');
    expect(linksText).toContain('Contact');
    expect(linksText).toContain('Demande de devis');
  });

  it('should show login when user is logged out', () => {
    authService.setLoggedIn(false);
    fixture.detectChanges();

    const navText = fixture.nativeElement.querySelector('.nav')?.textContent ?? '';
    expect(navText).toContain('Se connecter');
    expect(navText).not.toContain('Déconnexion');
  });

  it('should show logout when user is logged in', () => {
    authService.setLoggedIn(true);
    fixture.detectChanges();

    const navText = fixture.nativeElement.querySelector('.nav')?.textContent ?? '';
    expect(navText).toContain('Déconnexion');
    expect(navText).not.toContain('Se connecter');
  });

  it('should logout and navigate home on logout click', () => {
    authService.setLoggedIn(true);
    fixture.detectChanges();

    const logoutSpy = spyOn(authService, 'logout').and.callThrough();
    const navigateSpy = spyOn(router, 'navigate').and.resolveTo(true);

    component.onLogout();

    expect(logoutSpy).toHaveBeenCalled();
    expect(navigateSpy).toHaveBeenCalledWith(['/home']);
  });
});
