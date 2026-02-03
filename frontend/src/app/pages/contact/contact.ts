import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { HeaderComponent } from '../../components/header/header';
import { FooterComponent } from '../../components/footer/footer';

@Component({
  selector: 'app-contact',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink, HeaderComponent, FooterComponent],
  templateUrl: './contact.html',
  styleUrl: './contact.scss'
})
export class ContactComponent {
  contactForm: FormGroup;
  loading = false;
  successMessage = '';
  errorMessage = '';
  submitted = false;

  constructor(
    private fb: FormBuilder,
    private http: HttpClient
  ) {
    this.contactForm = this.fb.group({
      username: [''],
      title: ['', [Validators.required, Validators.minLength(5)]],
      email: ['', [Validators.email]],
      description: ['', [Validators.required, Validators.minLength(20), Validators.maxLength(2000)]]
    });
  }

  onSubmit(): void {
    // Validation conditionnelle : email requis si pas de username
    if (!this.contactForm.get('username')?.value && !this.contactForm.get('email')?.value) {
      this.errorMessage = 'L\'email est requis si vous ne fournissez pas de nom d\'utilisateur';
      return;
    }

    if (this.contactForm.invalid) {
      Object.keys(this.contactForm.controls).forEach(key => {
        this.contactForm.get(key)?.markAsTouched();
      });
      return;
    }

    this.loading = true;
    this.errorMessage = '';

    this.http.post<any>('http://localhost:8080/api/contact/send.php', this.contactForm.value)
      .subscribe({
        next: (response) => {
          this.successMessage = response.message;
          this.loading = false;
          this.submitted = true;
          this.contactForm.reset();
        },
        error: (error) => {
          this.errorMessage = error.error?.message || 'Une erreur est survenue. Veuillez réessayer.';
          this.loading = false;
        }
      });
  }

  get f() {
    return this.contactForm.controls;
  }
}