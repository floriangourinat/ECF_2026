import { ComponentFixture, TestBed } from '@angular/core/testing';
import { LoginComponent } from './login';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { ReactiveFormsModule } from '@angular/forms';

describe('LoginComponent', () => {
  let component: LoginComponent;
  let fixture: ComponentFixture<LoginComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [
        LoginComponent,
        HttpClientTestingModule,
        RouterTestingModule,
        ReactiveFormsModule
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(LoginComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  // Tests de base
  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have a login form', () => {
    expect(component.loginForm).toBeTruthy();
  });

  it('should have email and password fields', () => {
    expect(component.loginForm.contains('email')).toBeTrue();
    expect(component.loginForm.contains('password')).toBeTrue();
  });

  // Tests de validation
  it('should mark email as invalid if empty', () => {
    const emailControl = component.loginForm.get('email');
    emailControl?.setValue('');
    expect(emailControl?.valid).toBeFalse();
  });

  it('should mark email as invalid if not valid format', () => {
    const emailControl = component.loginForm.get('email');
    emailControl?.setValue('notanemail');
    expect(emailControl?.valid).toBeFalse();
  });

  it('should mark email as valid if correct format', () => {
    const emailControl = component.loginForm.get('email');
    emailControl?.setValue('test@test.com');
    expect(emailControl?.valid).toBeTrue();
  });

  it('should mark password as invalid if empty', () => {
    const passwordControl = component.loginForm.get('password');
    passwordControl?.setValue('');
    expect(passwordControl?.valid).toBeFalse();
  });

  it('should mark form as valid when all fields filled', () => {
    component.loginForm.get('email')?.setValue('test@test.com');
    component.loginForm.get('password')?.setValue('password123');
    expect(component.loginForm.valid).toBeTrue();
  });

  // Test soumission
  it('should not submit if form invalid', () => {
    component.loginForm.get('email')?.setValue('');
    component.loginForm.get('password')?.setValue('');
    component.onSubmit();
    expect(component.loading).toBeFalse();
  });
});