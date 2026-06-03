import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
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

  selectedImage: File | null = null;
  imagePreview: string | null = null;

  eventTypes = ['SÃ©minaire', 'ConfÃ©rence', 'SoirÃ©e d\'entreprise', 'Team Building', 'Autre'];
  themes = ['Ã‰lÃ©gant', 'Tropical', 'RÃ©tro', 'High-Tech', 'Nature', 'Industriel'];

  statusLabels: { [key: string]: string } = {
    'draft': 'Brouillon',
    'client_review': 'En attente client',
    'accepted': 'AcceptÃ©',
    'in_progress': 'En cours',
    'completed': 'TerminÃ©',
    'cancelled': 'AnnulÃ©'
  };

  constructor(private http: HttpClient, private route: ActivatedRoute) {}

  ngOnInit(): void {
    this.loadEvents();
    this.loadClients();

    this.route.queryParams.subscribe(params => {
      if (params['from_prospect'] === '1') {
        this.openPrefilledModal(params);
      }
    });
  }

  loadEvents(): void {
    this.loading = true;
    let url = '/api/events/read_all.php?';

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
        this.error = 'Impossible de charger les Ã©vÃ©nements';
        this.loading = false;
      }
    });
  }

  loadClients(): void {
    this.http.get<any>('/api/clients/read.php').subscribe({
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

  openPrefilledModal(params: any): void {
    this.openCreateModal();

    const plannedDate = (params['planned_date'] || '').split('T')[0];
    const startDate = plannedDate ? `${plannedDate}T09:00` : '';
    const endDate = plannedDate ? `${plannedDate}T18:00` : '';

    const prospectName = [params['company_name'], params['first_name'], params['last_name']]
      .filter((value: string) => !!value)
      .join(' ');

    this.newEvent = {
      name: params['event_type'] ? `${params['event_type']} - ${prospectName}` : `Ã‰vÃ©nement - ${prospectName}`,
      client_id: params['client_id'] || '',
      start_date: startDate,
      end_date: endDate,
      location: params['location'] || '',
      event_type: params['event_type'] || '',
      theme: 'Ã‰lÃ©gant',
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
        this.createError = 'Le fichier doit Ãªtre une image.';
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
      return '/api' + path;
    }
    return path;
  }

  onImageError(event: any): void {
    event.target.style.display = 'none';
  }

  createEvent(): void {
    if (
      !this.newEvent.name ||
      !this.newEvent.client_id ||
      !this.newEvent.start_date ||
      !this.newEvent.end_date ||
      !this.newEvent.event_type ||
      !this.newEvent.theme
    ) {
      this.createError = 'Nom, client, dates, type et thÃ¨me sont requis';
      return;
    }

    this.createLoading = true;
    this.createError = '';

    this.http.post<any>('/api/events/create.php', this.newEvent)
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
          this.createError = err.error?.message || 'Erreur lors de la crÃ©ation';
          this.createLoading = false;
        }
      });
  }

  uploadImage(eventId: number): void {
    if (!this.selectedImage) return;

    const formData = new FormData();
    formData.append('image', this.selectedImage);
    formData.append('event_id', eventId.toString());

    this.http.post<any>('/api/events/upload_image.php', formData)
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
    if (!confirm(`Supprimer l'Ã©vÃ©nement "${event.name}" ?\n\nCette action supprimera Ã©galement tous les devis, notes et tÃ¢ches associÃ©s.`)) {
      return;
    }

    this.http.delete<any>('/api/events/delete.php', { body: { id: event.id } })
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
