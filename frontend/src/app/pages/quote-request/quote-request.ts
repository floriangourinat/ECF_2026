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
  readonly defaultSuccessMessage = 'Merci pour votre demande. Chloé vous recontactera dans les plus brefs délais pour discuter de votre projet.';
  runtimeSuccessMessage = this.defaultSuccessMessage;
  submitted = false;

  selectedImage: File | null = null;
  imagePreview: string | null = null;

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
      location: ['', [Validators.required, Validators.minLength(2)]],
      event_type: ['', Validators.required],
      planned_date: ['', Validators.required],
      estimated_participants: ['', [Validators.required, Validators.min(1), Validators.max(10000)]],
      needs_description: ['', [Validators.required, Validators.minLength(20), Validators.maxLength(2000)]]
    });

    this.loadSuccessMessage();
  }

  loadSuccessMessage(): void {
    this.http.get<any>('http://localhost:8080/api/settings/get_quote_success_message.php')
      .subscribe({
        next: (response) => {
          if (response?.message) {
            this.runtimeSuccessMessage = response.message;
          }
        },
        error: () => {
          this.runtimeSuccessMessage = this.defaultSuccessMessage;
        }
      });
  }

  onImageSelected(event: any): void {
    const file = event.target.files[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
      this.errorMessage = 'L\'image est trop volumineuse. Maximum 5 Mo.';
      return;
    }

    if (!file.type.startsWith('image/')) {
      this.errorMessage = 'Le fichier doit être une image.';
      return;
    }

    this.selectedImage = file;
    this.errorMessage = '';

    const reader = new FileReader();
    reader.onload = (e: any) => {
      this.imagePreview = e.target.result;
    };
    reader.readAsDataURL(file);
  }

  removeImage(fileInput: any): void {
    this.selectedImage = null;
    this.imagePreview = null;
    fileInput.value = '';
  }

  onSubmit(): void {
    if (this.quoteForm.invalid) {
      Object.keys(this.quoteForm.controls).forEach((key) => {
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
          const prospectId = response.prospect_id;

          if (this.selectedImage && prospectId) {
            this.uploadImage(prospectId);
          } else {
            this.successMessage = response.message || this.runtimeSuccessMessage;
            this.loading = false;
            this.submitted = true;
            this.quoteForm.reset();
          }
        },
        error: (error) => {
          this.errorMessage = error.error?.message || 'Une erreur est survenue. Veuillez réessayer.';
          this.loading = false;
        }
      });
  }

  uploadImage(prospectId: number): void {
    if (!this.selectedImage) return;

    const formData = new FormData();
    formData.append('image', this.selectedImage);
    formData.append('prospect_id', prospectId.toString());

    this.http.post<any>('http://localhost:8080/api/prospects/upload_image.php', formData)
      .subscribe({
        next: () => {
          this.successMessage = this.runtimeSuccessMessage;
          this.loading = false;
          this.submitted = true;
          this.quoteForm.reset();
          this.selectedImage = null;
          this.imagePreview = null;
        },
        error: () => {
          this.successMessage = this.runtimeSuccessMessage;
          this.loading = false;
          this.submitted = true;
          this.quoteForm.reset();
        }
      });
  }

  get f() {
    return this.quoteForm.controls;
  }
}
