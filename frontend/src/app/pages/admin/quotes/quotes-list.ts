import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';

interface Quote {
  id: number;
  event_id: number;
  event_name: string;
  event_date: string;
  client_company: string;
  first_name: string;
  last_name: string;
  total_ht: number;
  total_ttc: number;
  tax_rate: number;
  status: string;
  issue_date: string;
  created_at: string;
}

@Component({
  selector: 'app-quotes-list',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, AdminLayoutComponent],
  templateUrl: './quotes-list.html',
  styleUrls: ['./quotes-list.scss']
})
export class QuotesListComponent implements OnInit {
  quotes: Quote[] = [];
  loading = true;
  error = '';
  filterStatus = '';

  statusLabels: { [key: string]: string } = {
    'pending': 'En attente',
    'modification': 'Modification demandée',
    'accepted': 'Accepté',
    'refused': 'Refusé'
  };

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
    this.loadQuotes();
  }

  loadQuotes(): void {
    this.loading = true;
    let url = 'http://localhost:8080/api/quotes/read.php';
    
    if (this.filterStatus) {
      url += `?status=${this.filterStatus}`;
    }

    this.http.get<any>(url).subscribe({
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

  onFilterChange(): void {
    this.loadQuotes();
  }

  downloadPdf(quote: Quote): void {
    window.open(`http://localhost:8080/api/quotes/generate_pdf.php?id=${quote.id}`, '_blank');
  }

  deleteQuote(quote: Quote): void {
    if (!confirm(`Supprimer le devis #${quote.id} ?`)) {
      return;
    }

    this.http.delete<any>('http://localhost:8080/api/quotes/delete.php', { body: { id: quote.id } })
      .subscribe({
        next: () => {
          this.quotes = this.quotes.filter(q => q.id !== quote.id);
        },
        error: () => {
          alert('Erreur lors de la suppression');
        }
      });
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('fr-FR');
  }

  formatCurrency(amount: number): string {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
  }

  getStatusClass(status: string): string {
    const classes: { [key: string]: string } = {
      'pending': 'status-pending',
      'modification': 'status-modification',
      'accepted': 'status-accepted',
      'refused': 'status-refused'
    };
    return classes[status] || '';
  }
}