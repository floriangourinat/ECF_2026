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
    event_date: '',
    location: '',
    attendees_count: 0,
    budget: 0,
    status: 'draft',
    type_id: '',
    theme_id: '',
    image_path: ''
  };
  eventTypes: any[] = [];
  themes: any[] = [];
  loading = true;
  saving = false;
  error = '';
  success = '';

  // Image upload
  selectedImage: File | null = null;
  imagePreview: string | null = null;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private http: HttpClient
  ) {}

  ngOnInit(): void {
    this.eventId = this.route.snapshot.paramMap.get('id') || '';
    this.loadEventTypes();
    this.loadThemes();
    if (this.eventId) {
      this.loadEvent();
    }
  }

  loadEventTypes(): void {
    this.http.get<any>('http://localhost:8080/api/event-types/read.php').subscribe({
      next: (response) => {
        this.eventTypes = response.data || [];
      }
    });
  }

  loadThemes(): void {
    this.http.get<any>('http://localhost:8080/api/themes/read.php').subscribe({
      next: (response) => {
        this.themes = response.data || [];
      }
    });
  }

  loadEvent(): void {
    this.http.get<any>(`http://localhost:8080/api/events/read_detail.php?id=${this.eventId}`)
      .subscribe({
        next: (response) => {
          if (response.data && response.data.event) {
            const e = response.data.event;
            this.event = {
              id: e.id,
              name: e.name || '',
              description: e.description || '',
              event_date: e.event_date ? e.event_date.substring(0, 16) : '',
              location: e.location || '',
              attendees_count: e.attendees_count || 0,
              budget: e.budget || 0,
              status: e.status || 'draft',
              type_id: e.type_id || '',
              theme_id: e.theme_id || '',
              image_path: e.image_path || ''
            };
          }
          this.loading = false;
        },
        error: () => {
          this.error = 'Événement non trouvé';
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
        this.error = 'Le fichier doit être une image.';
        return;
      }

      this.selectedImage = file;
      
      const reader = new FileReader();
      reader.onload = (e: any) => {
        this.imagePreview = e.target.result;
      };
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
      return 'http://localhost:8080' + path;
    }
    return path || '/assets/images/event-default.jpg';
  }

  saveEvent(): void {
    if (!this.event.name) {
      this.error = 'Le nom de l\'événement est requis';
      return;
    }

    this.saving = true;
    this.error = '';
    this.success = '';

    this.http.put<any>('http://localhost:8080/api/events/update.php', this.event)
      .subscribe({
        next: (response) => {
          if (response.success) {
            // Upload image si sélectionnée
            if (this.selectedImage) {
              this.uploadImage();
            } else {
              this.success = 'Événement modifié avec succès';
              setTimeout(() => {
                this.router.navigate(['/admin/events', this.eventId]);
              }, 1500);
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

    this.http.post<any>('http://localhost:8080/api/events/upload_image.php', formData)
      .subscribe({
        next: () => {
          this.success = 'Événement et image modifiés avec succès';
          setTimeout(() => {
            this.router.navigate(['/admin/events', this.eventId]);
          }, 1500);
          this.saving = false;
        },
        error: () => {
          this.success = 'Événement modifié (erreur upload image)';
          this.saving = false;
        }
      });
  }
}