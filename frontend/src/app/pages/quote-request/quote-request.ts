import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { HeaderComponent } from '../../components/header/header';
import { FooterComponent } from '../../components/footer/footer';

@Component({
  selector: 'app-quote-request',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink, HeaderComponent, FooterComponent],
  templateUrl: './quote-request.html',
  styleUrl: './quote-request.scss'
})
export class QuoteRequestComponent {
  quoteForm: FormGroup;
  loading = false;
  errorMessage = '';
  successMessage = '';
  submitted = false;

  eventTypes = [
    { value: 'seminaire', label: 'Séminaire' },
    { value: 'conference', label: 'Conférence' },
    { value: 'soiree', label: 'Soirée d\'entreprise' },
    { value: 'team_building', label: 'Team Building' },
    { value: 'autre', label: 'Autre' }
  ];

  constructor(
    private fb: FormBuilder,
    private http: HttpClient
  ) {
    this.quoteForm = this.fb.group({
      company_name: ['', [Validators.required, Validators.minLength(2)]],
      last_name: ['', [Validators.required, Validators.minLength(2)]],
      first_name: ['', [Validators.required, Validators.minLength(2)]],
      email: ['', [Validators.required, Validators.email]],
      phone: ['', [Validators.required, Validators.pattern(/^[\d\s\+\-\.]{10,}$/)]],
      location: [''],
      event_type: ['', Validators.required],
      planned_date: ['', Validators.required],
      estimated_participants: ['', [Validators.required, Validators.min(1), Validators.max(10000)]],
      needs_description: ['', [Validators.required, Validators.minLength(20), Validators.maxLength(2000)]]
    });
  }

  onSubmit(): void {
    if (this.quoteForm.invalid) {
      Object.keys(this.quoteForm.controls).forEach(key => {
        this.quoteForm.get(key)?.markAsTouched();
      });
      return;
    }

    this.loading = true;
    this.errorMessage = '';
    this.successMessage = '';

    this.http.post<any>('http://localhost:8080/api/prospects/create.php', this.quoteForm.value)
      .subscribe({
        next: (response) => {
          this.successMessage = response.message;
          this.loading = false;
          this.submitted = true;
          this.quoteForm.reset();
        },
        error: (error) => {
          this.errorMessage = error.error?.message || 'Une erreur est survenue. Veuillez réessayer.';
          this.loading = false;
        }
      });
  }

  get f() {
    return this.quoteForm.controls;
  }
}