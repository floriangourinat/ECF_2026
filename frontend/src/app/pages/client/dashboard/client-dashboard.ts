import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { ClientLayoutComponent } from '../../../components/client-layout/client-layout';
import { AuthService } from '../../../_services/auth.service';

interface Quote {
  id: number;
  event_name: string;
  event_date: string;
  total_ttc: number;
  status: string;
}

interface EventItem {
  id: number;
  name: string;
  start_date: string;
  location: string;
  status: string;
}

@Component({
  selector: 'app-client-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink, ClientLayoutComponent],
  templateUrl: './client-dashboard.html',
  styleUrls: ['./client-dashboard.scss']
})
export class ClientDashboardComponent implements OnInit {
  loading = true;
  clientId: number | null = null;
  upcomingEvents: EventItem[] = [];
  latestQuotes: Quote[] = [];

  statusLabels: { [key: string]: string } = {
    'pending': 'En attente',
    'modification': 'Modification demandée',
    'accepted': 'Accepté',
    'refused': 'Refusé'
  };

  constructor(private http: HttpClient, public authService: AuthService) {}

  ngOnInit(): void {
    const userId = this.authService.currentUserValue?.id;
    if (!userId) return;

    this.http.get<any>(`http://localhost:8080/api/clients/read_by_user.php?user_id=${userId}`).subscribe({
      next: (response) => {
        this.clientId = response.data?.client_id;
        this.loadUpcomingEvents();
        this.loadLatestQuotes();
      },
      error: () => {
        this.loading = false;
      }
    });
  }

  loadUpcomingEvents(): void {
    if (!this.clientId) return;
    this.http.get<any>(`http://localhost:8080/api/events/read_all.php?client_id=${this.clientId}`).subscribe({
      next: (response) => {
        const now = new Date();
        const events = response.data || [];
        this.upcomingEvents = events
          .filter((e: EventItem) => new Date(e.start_date) >= now)
          .sort((a: EventItem, b: EventItem) => new Date(a.start_date).getTime() - new Date(b.start_date).getTime())
          .slice(0, 3);
        this.loading = false;
      },
      error: () => {
        this.loading = false;
      }
    });
  }

  loadLatestQuotes(): void {
    const userId = this.authService.currentUserValue?.id;
    if (!userId) return;
    this.http.get<any>(`http://localhost:8080/api/quotes/read_by_client.php?user_id=${userId}`).subscribe({
      next: (response) => {
        this.latestQuotes = (response.data || []).slice(0, 3);
      },
      error: () => {}
    });
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('fr-FR');
  }

  formatCurrency(amount: number): string {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
  }
}
