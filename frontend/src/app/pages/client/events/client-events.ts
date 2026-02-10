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

interface ClientReview {
  id: number;
  event_id: number;
  event_name: string;
  rating: number;
  comment: string;
  status: string;
  created_at: string;
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
  myReviews: ClientReview[] = [];
  loading = true;
  error = '';
  clientId: number | null = null;
  reviewRating: Record<number, number> = {};
  reviewComment: Record<number, string> = {};
  submittingReview: Record<number, boolean> = {};
  reviewSuccessMessage: Record<number, string> = {};

  statusLabels: Record<string, string> = {
    draft: 'Brouillon',
    client_review: 'En attente client',
    accepted: 'Accepté',
    in_progress: 'En cours',
    completed: 'Terminé',
    cancelled: 'Annulé'
  };

  reviewStatusLabels: Record<string, string> = {
    pending: 'En attente de modération',
    approved: 'Approuvé',
    rejected: 'Rejeté'
  };

  constructor(private http: HttpClient, private authService: AuthService) {}

  ngOnInit(): void {
    const userId = this.authService.currentUserValue?.id;
    if (!userId) {
      this.loading = false;
      this.error = 'Utilisateur non connecté';
      return;
    }

    this.loadMyReviews(userId);

    this.http.get<any>(`http://localhost:8080/api/clients/read_by_user.php?user_id=${userId}`).subscribe({
      next: (response) => {
        this.clientId = response?.data?.client_id ?? null;

        if (!this.clientId) {
          this.error = 'Profil client introuvable';
          this.loading = false;
          return;
        }

        this.loadEvents();
      },
      error: () => {
        this.error = 'Impossible de récupérer le profil client';
        this.loading = false;
      }
    });
  }

  loadMyReviews(userId: number): void {
    this.http.get<any>(`http://localhost:8080/api/reviews/read_by_client.php?user_id=${userId}`).subscribe({
      next: (response) => {
        this.myReviews = response?.data || [];
      },
      error: () => {
        this.myReviews = [];
      }
    });
  }

  loadEvents(): void {
    if (!this.clientId) {
      this.loading = false;
      return;
    }

    this.http.get<any>(`http://localhost:8080/api/events/read_all.php?client_id=${this.clientId}`).subscribe({
      next: (response) => {
        this.events = response?.data || [];
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les événements';
        this.loading = false;
      }
    });
  }

  hasReview(eventId: number): boolean {
    return this.myReviews.some((review) => Number(review.event_id) === Number(eventId));
  }

  getReviewByEvent(eventId: number): ClientReview | undefined {
    return this.myReviews.find((review) => Number(review.event_id) === Number(eventId));
  }

  submitReview(event: EventItem): void {
    const userId = this.authService.currentUserValue?.id;
    if (!userId || this.submittingReview[event.id]) {
      return;
    }

    if (this.hasReview(event.id)) {
      alert('Vous avez déjà laissé un avis pour cet événement.');
      return;
    }

    const rating = Number(this.reviewRating[event.id] || 0);
    if (!rating || rating < 1 || rating > 5) {
      alert('Merci de sélectionner une note entre 1 et 5.');
      return;
    }

    this.submittingReview[event.id] = true;
    this.reviewSuccessMessage[event.id] = '';

    this.http.post<any>('http://localhost:8080/api/reviews/create.php', {
      user_id: userId,
      event_id: event.id,
      rating,
      comment: (this.reviewComment[event.id] || '').trim()
    }).subscribe({
      next: (response) => {
        if (response?.data) {
          this.myReviews.unshift({
            id: Number(response.data.id),
            event_id: Number(response.data.event_id),
            event_name: event.name,
            rating: Number(response.data.rating),
            comment: response.data.comment || '',
            status: response.data.status || 'pending',
            created_at: response.data.created_at || new Date().toISOString()
          });
        } else {
          this.loadMyReviews(userId);
        }

        this.reviewRating[event.id] = 0;
        this.reviewComment[event.id] = '';
        this.reviewSuccessMessage[event.id] = '✅ Avis envoyé avec succès.';
        this.submittingReview[event.id] = false;
      },
      error: (err) => {
        if (err?.status === 409) {
          this.loadMyReviews(userId);
        }

        this.submittingReview[event.id] = false;
        alert(err?.error?.message || 'Erreur lors de l\'envoi de l\'avis');
      }
    });
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('fr-FR');
  }
}
