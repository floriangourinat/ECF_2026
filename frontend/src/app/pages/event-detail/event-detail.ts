import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { HeaderComponent } from '../../components/header/header';
import { FooterComponent } from '../../components/footer/footer';

interface Event {
  id: number;
  name: string;
  event_type: string;
  theme: string;
  location: string;
  start_date: string;
  end_date: string;
  image_path: string;
  status: string;
  client_company: string;
}

@Component({
  selector: 'app-event-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, HeaderComponent, FooterComponent],
  templateUrl: './event-detail.html',
  styleUrls: ['./event-detail.scss']
})
export class EventDetailComponent implements OnInit {
  event: Event | null = null;
  loading = true;
  error = '';
  defaultImage = 'assets/images/event-default.jpg';

  statusLabels: Record<string, string> = {
    draft: 'Brouillon',
    client_review: 'En attente client',
    accepted: 'AcceptÃ©',
    in_progress: 'En cours',
    completed: 'TerminÃ©',
    cancelled: 'AnnulÃ©'
  };

  constructor(
    private route: ActivatedRoute,
    private http: HttpClient
  ) {}

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.loadEvent(id);
    }
  }

  loadEvent(id: string): void {
    this.http.get<any>(`/api/events/read_one.php?id=${id}`)
      .subscribe({
        next: (response) => {
          this.event = response.data;
          this.loading = false;
        },
        error: () => {
          this.error = 'Ã‰vÃ©nement non trouvÃ©';
          this.loading = false;
        }
      });
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('fr-FR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    });
  }

  formatTime(dateString: string): string {
    return new Date(dateString).toLocaleTimeString('fr-FR', {
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  getEventImage(imagePath: string | null): string {
    if (imagePath && imagePath.trim() !== '') {
      if (imagePath.startsWith('/uploads/')) {
        return '/api' + imagePath;
      }
      return imagePath;
    }
    return this.defaultImage;
  }

  onImageError(event: any): void {
    event.target.src = this.defaultImage;
  }

  getStatusLabel(status: string): string {
    return this.statusLabels[status] || status;
  }
}
