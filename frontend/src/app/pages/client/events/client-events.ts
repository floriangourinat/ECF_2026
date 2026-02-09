import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { ClientLayoutComponent } from '../../../components/client-layout/client-layout';
import { AuthService } from '../../../_services/auth.service';

interface EventItem {
  id: number;
  name: string;
  start_date: string;
  end_date: string;
  location: string;
  status: string;
}

@Component({
  selector: 'app-client-events',
  standalone: true,
  imports: [CommonModule, FormsModule, ClientLayoutComponent],
  templateUrl: './client-events.html',
  styleUrls: ['./client-events.scss']
})
export class ClientEventsComponent implements OnInit {
  events: EventItem[] = [];
  loading = true;
  error = '';
  clientId: number | null = null;
  reviewRating: { [key: number]: number } = {};
  reviewComment: { [key: number]: string } = {};
  reviewSent: { [key: number]: boolean } = {};

  statusLabels: { [key: string]: string } = {
    'draft': 'Brouillon',
    'client_review': 'En attente client',
    'accepted': 'Accepté',
    'in_progress': 'En cours',
    'completed': 'Terminé',
    'cancelled': 'Annulé'
  };

  constructor(private http: HttpClient, private authService: AuthService) {}

  ngOnInit(): void {
    const userId = this.authService.currentUserValue?.id;
    if (!userId) return;
    this.http.get<any>(`http://localhost:8080/api/clients/read_by_user.php?user_id=${userId}`).subscribe({
      next: (response) => {
        this.clientId = response.data?.client_id;
        this.loadEvents();
      },
      error: () => {
        this.loading = false;
      }
    });
  }

  loadEvents(): void {
    if (!this.clientId) return;
    this.http.get<any>(`http://localhost:8080/api/events/read_all.php?client_id=${this.clientId}`).subscribe({
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

  submitReview(event: EventItem): void {
    const userId = this.authService.currentUserValue?.id;
    if (!userId) return;

    if (!this.reviewRating[event.id]) {
      alert('Merci de sélectionner une note.');
      return;
    }

    this.http.post<any>('http://localhost:8080/api/reviews/create.php', {
      user_id: userId,
      event_id: event.id,
      rating: this.reviewRating[event.id],
      comment: this.reviewComment[event.id] || ''
    }).subscribe({
      next: () => {
        this.reviewSent[event.id] = true;
      },
      error: (err) => {
        alert(err?.error?.message || 'Erreur lors de l\'envoi de l\'avis');
      }
    });
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('fr-FR');
  }
}
