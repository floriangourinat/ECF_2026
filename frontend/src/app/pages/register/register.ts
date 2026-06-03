import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../_services/auth.service';
import { HeaderComponent } from '../../components/header/header';
import { FooterComponent } from '../../components/footer/footer';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink, HeaderComponent, FooterComponent],
  templateUrl: './register.html',
  styleUrl: './register.scss'
})
export class RegisterComponent {
  registerForm: FormGroup;
  loading = false;
  errorMessage = '';
  successMessage = '';

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router
  ) {
    // 8+ / 1 minuscule / 1 majuscule / 1 chiffre / 1 spécial (non alphanum)
    const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;

    this.registerForm = this.fb.group({
      last_name: ['', [Validators.required, Validators.maxLength(100)]],
      first_name: ['', [Validators.required, Validators.maxLength(100)]],
      username: ['', [Validators.required, Validators.minLength(3), Validators.maxLength(100)]],
      email: ['', [Validators.required, Validators.email, Validators.maxLength(150)]],
      password: ['', [
        Validators.required,
        Validators.pattern(passwordPattern)
      ]]
    });
  }

  onSubmit(): void {
    if (this.registerForm.invalid) return;

    this.loading = true;
    this.errorMessage = '';
    this.successMessage = '';

    this.authService.register(this.registerForm.value).subscribe({
      next: (response) => {
        this.successMessage = response.message || "Compte créé. Vérifiez vos emails pour confirmer votre adresse.";
        this.loading = false;

        // Redirection vers login avec email pré-rempli
        const email = this.registerForm.value.email;
        setTimeout(() => {
          this.router.navigate(['/login'], { queryParams: { email } });
        }, 1500);
      },
      error: (error) => {
        this.errorMessage = error.error?.message || "Erreur lors de la création du compte";
        this.loading = false;
      }
    });
  }

  get f() {
    return this.registerForm.controls;
  }
}
