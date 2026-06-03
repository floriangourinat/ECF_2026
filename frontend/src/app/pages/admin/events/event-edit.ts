import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';

@Component({
  selector: 'app-event-edit',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, AdminLayoutComponent],
  templateUrl: './event-edit.html',
  styleUrls: ['./event-edit.scss']
})
export class EventEditComponent implements OnInit {
  eventId: string = '';
  event: any = {
    id: '',
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    location: '',
    attendees_count: 0,
    budget: 0,
    status: 'draft',
    event_type: '',
    theme: '',
    image_path: '',
    is_visible: false
  };
  eventTypes: string[] = ['SÃ©minaire', 'ConfÃ©rence', 'SoirÃ©e d\'entreprise', 'Team Building', 'Autre'];
  themes: string[] = ['Ã‰lÃ©gant', 'Tropical', 'RÃ©tro', 'High-Tech', 'Nature', 'Industriel'];
  loading = true;
  saving = false;
  error = '';
  success = '';

  selectedImage: File | null = null;
  imagePreview: string | null = null;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private http: HttpClient
  ) {}

  ngOnInit(): void {
    this.eventId = this.route.snapshot.paramMap.get('id') || '';
    if (this.eventId) {
      this.loadEvent();
    }
  }

  loadEvent(): void {
    this.http.get<any>(`/api/events/read_detail.php?id=${this.eventId}`)
      .subscribe({
        next: (response) => {
          if (response.data && response.data.event) {
            const e = response.data.event;
            this.event = {
              id: e.id,
              name: e.name || '',
              description: e.description || '',
              start_date: e.start_date ? e.start_date.substring(0, 16) : '',
              end_date: e.end_date ? e.end_date.substring(0, 16) : '',
              location: e.location || '',
              attendees_count: e.attendees_count || 0,
              budget: e.budget || 0,
              status: e.status || 'draft',
              event_type: e.event_type || '',
              theme: e.theme || '',
              image_path: e.image_path || '',
              is_visible: e.is_visible == 1 || e.is_visible === true
            };
          }
          this.loading = false;
        },
        error: () => {
          this.error = 'Ã‰vÃ©nement non trouvÃ©';
          this.loading = false;
        }
      });
  }

  onImageSelected(event: any): void {
    const file = event.target.files[0];
    if (file) {
      if (file.size > 5 * 1024 * 1024) {
        this.error = 'L\'image est trop volumineuse. Maximum 5 Mo.';
        return;
      }
      if (!file.type.startsWith('image/')) {
        this.error = 'Le fichier doit Ãªtre une image.';
        return;
      }
      this.selectedImage = file;
      const reader = new FileReader();
      reader.onload = (e: any) => { this.imagePreview = e.target.result; };
      reader.readAsDataURL(file);
    }
  }

  removeImage(fileInput: any): void {
    this.selectedImage = null;
    this.imagePreview = null;
    fileInput.value = '';
  }

  removeCurrentImage(): void {
    this.event.image_path = '';
  }

  getImageUrl(path: string): string {
    if (path && path.startsWith('/uploads/')) {
      return '/api' + path;
    }
    return path || 'assets/images/event-default.jpg';
  }

  saveEvent(): void {
    if (!this.event.name) {
      this.error = 'Le nom de l\'Ã©vÃ©nement est requis';
      return;
    }

    if (!this.event.event_type) {
      this.error = 'Le type d\'Ã©vÃ©nement est requis';
      return;
    }

    if (!this.event.theme) {
      this.error = 'Le thÃ¨me est requis';
      return;
    }

    this.saving = true;
    this.error = '';
    this.success = '';

    // Convertir is_visible en 0/1 pour le backend
    const payload = {
      ...this.event,
      is_visible: this.event.is_visible ? 1 : 0
    };

    this.http.put<any>('/api/events/update.php', payload)
      .subscribe({
        next: (response) => {
          if (response.success) {
            if (this.selectedImage) {
              this.uploadImage();
            } else {
              this.success = 'Ã‰vÃ©nement modifiÃ© avec succÃ¨s';
              setTimeout(() => { this.router.navigate(['/admin/events', this.eventId]); }, 1500);
              this.saving = false;
            }
          } else {
            this.error = response.message || 'Erreur lors de la modification';
            this.saving = false;
          }
        },
        error: (err) => {
          this.error = err.error?.message || 'Erreur serveur';
          this.saving = false;
        }
      });
  }

  uploadImage(): void {
    if (!this.selectedImage) return;

    const formData = new FormData();
    formData.append('image', this.selectedImage);
    formData.append('event_id', this.eventId);

    this.http.post<any>('/api/events/upload_image.php', formData)
      .subscribe({
        next: () => {
          this.success = 'Ã‰vÃ©nement et image modifiÃ©s avec succÃ¨s';
          setTimeout(() => { this.router.navigate(['/admin/events', this.eventId]); }, 1500);
          this.saving = false;
        },
        error: () => {
          this.success = 'Ã‰vÃ©nement modifiÃ© (erreur upload image)';
          this.saving = false;
        }
      });
  }
}
