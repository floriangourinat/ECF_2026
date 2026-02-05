import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';
import { AuthService } from '../../../_services/auth.service';

interface Review {
  id: number;
  event_id: number;
  client_id: number;
  rating: number;
  comment: string;
  status: string;
  event_name: string;
  company_name: string;
  first_name: string;
  last_name: string;
  email: string;
  created_at: string;
}

@Component({
  selector: 'app-admin-reviews-list',
  standalone: true,
  imports: [CommonModule, FormsModule, AdminLayoutComponent],
  templateUrl: './reviews-list.html',
  styleUrls: ['./reviews-list.scss']
})
export class AdminReviewsListComponent implements OnInit {
  reviews: Review[] = [];
  loading = true;
  error = '';
  filterStatus = '';

  statusLabels: { [key: string]: string } = {
    'pending': 'En attente',
    'approved': 'Approuvé',
    'rejected': 'Rejeté'
  };

  constructor(
    private http: HttpClient,
    private authService: AuthService
  ) {}

  ngOnInit(): void {
    this.loadReviews();
  }

  loadReviews(): void {
    this.loading = true;
    let url = 'http://localhost:8080/api/reviews/read_all.php';
    
    if (this.filterStatus) {
      url += `?status=${this.filterStatus}`;
    }

    this.http.get<any>(url).subscribe({
      next: (response) => {
        this.reviews = response.data || [];
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les avis';
        this.loading = false;
      }
    });
  }

  onFilterChange(): void {
    this.loadReviews();
  }

  approveReview(review: Review): void {
    this.updateStatus(review, 'approved');
  }

  rejectReview(review: Review): void {
    this.updateStatus(review, 'rejected');
  }

  updateStatus(review: Review, status: string): void {
    const currentUser = this.authService.currentUserValue;
    
    this.http.put<any>('http://localhost:8080/api/reviews/update_status.php', {
      id: review.id,
      status: status,
      reviewed_by: currentUser?.id
    }).subscribe({
      next: () => {
        review.status = status;
      },
      error: () => {
        alert('Erreur lors de la modération');
      }
    });
  }

  deleteReview(review: Review): void {
    if (!confirm('Supprimer définitivement cet avis ?')) {
      return;
    }

    this.http.delete<any>('http://localhost:8080/api/reviews/delete.php', { body: { id: review.id } })
      .subscribe({
        next: () => {
          this.reviews = this.reviews.filter(r => r.id !== review.id);
        },
        error: () => {
          alert('Erreur lors de la suppression');
        }
      });
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('fr-FR');
  }

  getStars(rating: number): string {
    return '★'.repeat(rating) + '☆'.repeat(5 - rating);
  }

  getStatusClass(status: string): string {
    const classes: { [key: string]: string } = {
      'pending': 'status-pending',
      'approved': 'status-approved',
      'rejected': 'status-rejected'
    };
    return classes[status] || '';
  }
}