import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';

@Component({
  selector: 'app-client-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, AdminLayoutComponent],
  templateUrl: './client-detail.html',
  styleUrls: ['./client-detail.scss']
})
export class ClientDetailComponent implements OnInit {
  client: any = null;
  events: any[] = [];
  quotes: any[] = [];
  loading = true;
  error = '';

  statusLabels: { [key: string]: string } = {
    'draft': 'Brouillon',
    'client_review': 'En attente',
    'accepted': 'Accepté',
    'in_progress': 'En cours',
    'completed': 'Terminé',
    'cancelled': 'Annulé'
  };

  quoteStatusLabels: { [key: string]: string } = {
    'pending': 'En attente',
    'modification': 'Modification demandée',
    'accepted': 'Accepté',
    'refused': 'Refusé'
  };

  constructor(
    private route: ActivatedRoute,
    private http: HttpClient
  ) {}

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.loadClient(id);
    }
  }

  loadClient(id: string): void {
    this.http.get<any>(`http://localhost:8080/api/clients/read_one.php?id=${id}`)
      .subscribe({
        next: (response) => {
          this.client = response.data.client;
          this.events = response.data.events || [];
          this.quotes = response.data.quotes || [];
          this.loading = false;
        },
        error: () => {
          this.error = 'Client non trouvé';
          this.loading = false;
        }
      });
  }

  formatDate(dateString: string): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR');
  }

  formatCurrency(amount: number): string {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
  }
}