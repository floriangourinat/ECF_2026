import { ComponentFixture, TestBed } from '@angular/core/testing';
import { RegisterComponent } from './register';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { ReactiveFormsModule } from '@angular/forms';

describe('RegisterComponent', () => {
  let component: RegisterComponent;
  let fixture: ComponentFixture<RegisterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [
        RegisterComponent,
        HttpClientTestingModule,
        RouterTestingModule,
        ReactiveFormsModule
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(RegisterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should have register form with required fields', () => {
    expect(component.registerForm).toBeTruthy();
    expect(component.registerForm.contains('last_name')).toBeTrue();
    expect(component.registerForm.contains('first_name')).toBeTrue();
    expect(component.registerForm.contains('username')).toBeTrue();
    expect(component.registerForm.contains('email')).toBeTrue();
    expect(component.registerForm.contains('password')).toBeTrue();
  });

  it('should mark form invalid if empty', () => {
    component.registerForm.reset();
    expect(component.registerForm.valid).toBeFalse();
  });

  it('should reject password missing uppercase', () => {
    component.registerForm.get('password')?.setValue('password1!');
    expect(component.registerForm.get('password')?.valid).toBeFalse();
  });

  it('should reject password missing lowercase', () => {
    component.registerForm.get('password')?.setValue('PASSWORD1!');
    expect(component.registerForm.get('password')?.valid).toBeFalse();
  });

  it('should reject password missing number', () => {
    component.registerForm.get('password')?.setValue('Password!');
    expect(component.registerForm.get('password')?.valid).toBeFalse();
  });

  it('should reject password missing special character', () => {
    component.registerForm.get('password')?.setValue('Password1');
    expect(component.registerForm.get('password')?.valid).toBeFalse();
  });

  it('should accept strong password', () => {
    component.registerForm.get('password')?.setValue('Password1!');
    expect(component.registerForm.get('password')?.valid).toBeTrue();
  });
});
