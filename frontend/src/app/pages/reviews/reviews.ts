import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { HeaderComponent } from '../../components/header/header';
import { FooterComponent } from '../../components/footer/footer';

interface Review {
  id: number;
  rating: number;
  comment: string;
  created_at: string;
  company_name: string;
  first_name: string;
  last_name: string;
  event_name: string;
  event_type: string;
}

@Component({
  selector: 'app-reviews',
  standalone: true,
  imports: [CommonModule, RouterLink, HeaderComponent, FooterComponent],
  templateUrl: './reviews.html',
  styleUrl: './reviews.scss'
})
export class ReviewsComponent implements OnInit {
  reviews: Review[] = [];
  loading = true;
  error = '';

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
    this.loadReviews();
  }

  loadReviews(): void {
    this.http.get<any>('http://localhost:8080/api/reviews/read_public.php')
      .subscribe({
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

  getStars(rating: number): string[] {
    const stars = [];
    for (let i = 1; i <= 5; i++) {
      stars.push(i <= rating ? '★' : '☆');
    }
    return stars;
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    });
  }

  getAverageRating(): number {
    if (this.reviews.length === 0) return 0;
    const sum = this.reviews.reduce((acc, r) => acc + r.rating, 0);
    return Math.round((sum / this.reviews.length) * 10) / 10;
  }
}