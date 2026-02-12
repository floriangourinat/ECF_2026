import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { LoginPage } from './login.page';
import { AuthService } from '../../services/auth.service';

describe('LoginPage', () => {
  let component: LoginPage;
  let fixture: ComponentFixture<LoginPage>;

  beforeEach(async () => {
    localStorage.clear();
    await TestBed.configureTestingModule({
      imports: [HttpClientTestingModule, RouterTestingModule, LoginPage],
      providers: [AuthService]
    }).compileComponents();
    fixture = TestBed.createComponent(LoginPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => { expect(component).toBeTruthy(); });
  it('should init with empty fields', () => { expect(component.email).toBe(''); expect(component.password).toBe(''); expect(component.loading).toBeFalse(); });
  it('should error when fields empty', () => { component.onLogin(); expect(component.error).toBe('Veuillez remplir tous les champs'); });
  it('should error when only email', () => { component.email = 't@t.com'; component.onLogin(); expect(component.error).toBe('Veuillez remplir tous les champs'); });
  it('should error when only password', () => { component.password = 'p'; component.onLogin(); expect(component.error).toBe('Veuillez remplir tous les champs'); });
  it('should set loading when valid', () => { component.email = 't@t.com'; component.password = 'p'; component.onLogin(); expect(component.loading).toBeTrue(); });
});
