import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { ClientLayoutComponent } from '../../../components/client-layout/client-layout';
import { AuthService } from '../../../_services/auth.service';

interface Quote {
  id: number;
  event_id: number;
  event_name: string;
  event_date: string;
  event_location: string;
  total_ttc: number;
  status: string;
  created_at: string;
}

@Component({
  selector: 'app-client-quotes',
  standalone: true,
  imports: [CommonModule, FormsModule, ClientLayoutComponent],
  templateUrl: './client-quotes.html',
  styleUrls: ['./client-quotes.scss']
})
export class ClientQuotesComponent implements OnInit {
  quotes: Quote[] = [];
  loading = true;
  error = '';
  actionLoading: { [key: number]: boolean } = {};
  modificationReason: { [key: number]: string } = {};
  showModificationForm: { [key: number]: boolean } = {};

  statusLabels: { [key: string]: string } = {
    'pending': 'En attente',
    'modification': 'Modification demandée',
    'accepted': 'Accepté',
    'refused': 'Refusé'
  };

  constructor(private http: HttpClient, private authService: AuthService) {}

  ngOnInit(): void {
    this.loadQuotes();
  }

  loadQuotes(): void {
    const userId = this.authService.currentUserValue?.id;
    if (!userId) return;

    this.loading = true;
    this.http.get<any>(`http://localhost:8080/api/quotes/read_by_client.php?user_id=${userId}`).subscribe({
      next: (response) => {
        this.quotes = response.data || [];
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les devis';
        this.loading = false;
      }
    });
  }

  updateStatus(quote: Quote, status: 'accepted' | 'modification' | 'refused'): void {
    const userId = this.authService.currentUserValue?.id;
    if (!userId) return;

    if (status === 'modification' && !this.modificationReason[quote.id]) {
      alert('Veuillez préciser un motif de modification.');
      return;
    }

    this.actionLoading[quote.id] = true;

    this.http.put<any>('http://localhost:8080/api/quotes/client_update_status.php', {
      user_id: userId,
      quote_id: quote.id,
      status,
      modification_reason: this.modificationReason[quote.id] || null
    }).subscribe({
      next: () => {
        quote.status = status;
        this.showModificationForm[quote.id] = false;
        this.actionLoading[quote.id] = false;
      },
      error: (err) => {
        alert(err?.error?.message || 'Erreur lors de la mise à jour');
        this.actionLoading[quote.id] = false;
      }
    });
  }

  toggleModification(quoteId: number): void {
    this.showModificationForm[quoteId] = !this.showModificationForm[quoteId];
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('fr-FR');
  }

  formatCurrency(amount: number): string {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
  }

  downloadPdf(quote: Quote): void {
    window.open(`http://localhost:8080/api/quotes/generate_pdf.php?id=${quote.id}`, '_blank');
  }
}
