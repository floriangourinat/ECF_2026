import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { EmployeeLayoutComponent } from '../../../components/employee-layout/employee-layout';

@Component({
  selector: 'app-employee-reviews',
  standalone: true,
  imports: [CommonModule, EmployeeLayoutComponent],
  template: `
    <app-employee-layout>
      <div class="page">
        <header class="page-header"><h1>⭐ Modération des avis</h1></header>

        <div *ngIf="loading" class="loading">Chargement...</div>

        <!-- Avis en attente -->
        <section *ngIf="!loading" class="section">
          <h3>En attente de modération ({{ pendingReviews.length }})</h3>
          <div *ngIf="pendingReviews.length === 0" class="empty">Aucun avis en attente</div>
          <div class="reviews-list">
            <div *ngFor="let review of pendingReviews" class="review-card pending">
              <div class="review-header">
                <div>
                  <strong>{{ review.author_name }}</strong>
                  <span class="stars">{{ getStars(review.rating) }}</span>
                </div>
                <small>{{ review.event_name }} · {{ formatDate(review.created_at) }}</small>
              </div>
              <p class="review-text">{{ review.comment }}</p>
              <div class="review-actions">
                <button class="btn-approve" (click)="updateStatus(review, 'approved')">✅ Approuver</button>
                <button class="btn-reject" (click)="updateStatus(review, 'rejected')">❌ Refuser</button>
              </div>
            </div>
          </div>
        </section>

        <!-- Avis traités -->
        <section *ngIf="!loading && processedReviews.length > 0" class="section">
          <h3>Avis traités ({{ processedReviews.length }})</h3>
          <div class="reviews-list">
            <div *ngFor="let review of processedReviews" class="review-card" [class.approved]="review.status === 'approved'" [class.rejected]="review.status === 'rejected'">
              <div class="review-header">
                <div>
                  <strong>{{ review.author_name }}</strong>
                  <span class="stars">{{ getStars(review.rating) }}</span>
                  <span class="status-tag" [class]="review.status">{{ review.status === 'approved' ? 'Approuvé' : 'Refusé' }}</span>
                </div>
                <small>{{ review.event_name }}</small>
              </div>
              <p class="review-text">{{ review.comment }}</p>
            </div>
          </div>
        </section>
      </div>
    </app-employee-layout>
  `,
  styleUrls: ['./employee-reviews.scss']
})
export class EmployeeReviewsComponent implements OnInit {
  reviews: any[] = [];
  loading = true;

  constructor(private http: HttpClient) {}
  ngOnInit(): void { this.loadReviews(); }

  get pendingReviews() { return this.reviews.filter(r => r.status === 'pending'); }
  get processedReviews() { return this.reviews.filter(r => r.status !== 'pending'); }

  loadReviews(): void {
    this.http.get<any>('http://localhost:8080/api/reviews/read_all.php').subscribe({
      next: (r) => { this.reviews = r.data || []; this.loading = false; },
      error: () => { this.loading = false; }
    });
  }

  updateStatus(review: any, status: string): void {
    this.http.put<any>('http://localhost:8080/api/reviews/update_status.php', { id: review.id, status }).subscribe({
      next: (r) => { if (r.success) review.status = status; }
    });
  }

  getStars(n: number): string { return '⭐'.repeat(n); }
  formatDate(d: string): string { return d ? new Date(d).toLocaleDateString('fr-FR') : '-'; }
}
