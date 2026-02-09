import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';

interface Event {
  id: number;
  name: string;
  start_date: string;
  end_date: string;
  location: string;
  event_type: string;
  theme: string;
  status: string;
  is_visible: boolean;
  image_path: string;
  client_id: number;
  client_company: string;
  client_first_name: string;
  client_last_name: string;
}

interface Client {
  id: number;
  company_name: string;
  first_name: string;
  last_name: string;
}

@Component({
  selector: 'app-events-list',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, AdminLayoutComponent],
  templateUrl: './events-list.html',
  styleUrls: ['./events-list.scss']
})
export class EventsListComponent implements OnInit {
  events: Event[] = [];
  clients: Client[] = [];
  loading = true;
  error = '';
  searchTerm = '';
  filterStatus = '';

  showCreateModal = false;
  createLoading = false;
  createError = '';
  newEvent = {
    name: '',
    client_id: '',
    start_date: '',
    end_date: '',
    location: '',
    event_type: '',
    theme: '',
    status: 'draft',
    is_visible: false
  };

  // Image upload
  selectedImage: File | null = null;
  imagePreview: string | null = null;

  eventTypes = ['Séminaire', 'Conférence', 'Soirée d\'entreprise', 'Team Building', 'Autre'];
  themes = ['Élégant', 'Tropical', 'Rétro', 'High-Tech', 'Nature', 'Industriel'];
  
  statusLabels: { [key: string]: string } = {
    'draft': 'Brouillon',
    'client_review': 'En attente client',
    'accepted': 'Accepté',
    'in_progress': 'En cours',
    'completed': 'Terminé',
    'cancelled': 'Annulé'
  };

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
    this.loadEvents();
    this.loadClients();
  }

  loadEvents(): void {
    this.loading = true;
    let url = 'http://localhost:8080/api/events/read_all.php?';
    
    if (this.filterStatus) {
      url += `status=${this.filterStatus}&`;
    }
    if (this.searchTerm) {
      url += `search=${encodeURIComponent(this.searchTerm)}`;
    }

    this.http.get<any>(url).subscribe({
      next: (response) => {
        this.events = response.data || [];
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les événements';
        this.loading = false;
      }
    });
  }

  loadClients(): void {
    this.http.get<any>('http://localhost:8080/api/clients/read.php').subscribe({
      next: (response) => {
        this.clients = response.data || [];
      }
    });
  }

  onSearch(): void {
    this.loadEvents();
  }

  onFilterChange(): void {
    this.loadEvents();
  }

  openCreateModal(): void {
    this.showCreateModal = true;
    this.createError = '';
    this.selectedImage = null;
    this.imagePreview = null;
    this.newEvent = {
      name: '',
      client_id: '',
      start_date: '',
      end_date: '',
      location: '',
      event_type: '',
      theme: '',
      status: 'draft',
      is_visible: false
    };
  }

  closeCreateModal(): void {
    this.showCreateModal = false;
    this.selectedImage = null;
    this.imagePreview = null;
  }

  onImageSelected(event: any): void {
    const file = event.target.files[0];
    if (file) {
      if (file.size > 5 * 1024 * 1024) {
        this.createError = 'L\'image est trop volumineuse. Maximum 5 Mo.';
        return;
      }
      
      if (!file.type.startsWith('image/')) {
        this.createError = 'Le fichier doit être une image.';
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

  getImageUrl(path: string): string {
    if (path && path.startsWith('/uploads/')) {
      return 'http://localhost:8080' + path;
    }
    return path;
  }

  onImageError(event: any): void {
    event.target.style.display = 'none';
  }

  createEvent(): void {
    if (!this.newEvent.name || !this.newEvent.client_id || !this.newEvent.start_date || !this.newEvent.end_date) {
      this.createError = 'Nom, client, date de début et date de fin sont requis';
      return;
    }

    this.createLoading = true;
    this.createError = '';

    this.http.post<any>('http://localhost:8080/api/events/create.php', this.newEvent)
      .subscribe({
        next: (response) => {
          const eventId = response.data?.id;
          
          if (this.selectedImage && eventId) {
            this.uploadImage(eventId);
          } else {
            this.closeCreateModal();
            this.loadEvents();
            this.createLoading = false;
          }
        },
        error: (err) => {
          this.createError = err.error?.message || 'Erreur lors de la création';
          this.createLoading = false;
        }
      });
  }

  uploadImage(eventId: number): void {
    if (!this.selectedImage) return;

    const formData = new FormData();
    formData.append('image', this.selectedImage);
    formData.append('event_id', eventId.toString());

    this.http.post<any>('http://localhost:8080/api/events/upload_image.php', formData)
      .subscribe({
        next: () => {
          this.closeCreateModal();
          this.loadEvents();
          this.createLoading = false;
        },
        error: () => {
          this.closeCreateModal();
          this.loadEvents();
          this.createLoading = false;
        }
      });
  }

  deleteEvent(event: Event): void {
    if (!confirm(`Supprimer l'événement "${event.name}" ?\n\nCette action supprimera également tous les devis, notes et tâches associés.`)) {
      return;
    }

    this.http.delete<any>('http://localhost:8080/api/events/delete.php', { body: { id: event.id } })
      .subscribe({
        next: () => {
          this.events = this.events.filter(e => e.id !== event.id);
        },
        error: () => {
          alert('Erreur lors de la suppression');
        }
      });
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('fr-FR');
  }

  getStatusClass(status: string): string {
    const classes: { [key: string]: string } = {
      'draft': 'status-draft',
      'client_review': 'status-review',
      'accepted': 'status-accepted',
      'in_progress': 'status-progress',
      'completed': 'status-completed',
      'cancelled': 'status-cancelled'
    };
    return classes[status] || '';
  }
}