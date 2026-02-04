import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';

@Component({
  selector: 'app-quote-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, AdminLayoutComponent],
  templateUrl: './quote-detail.html',
  styleUrls: ['./quote-detail.scss']
})
export class QuoteDetailComponent implements OnInit {
  quote: any = null;
  services: any[] = [];
  loading = true;
  error = '';

  statusLabels: { [key: string]: string } = {
    'pending': 'En attente',
    'modification': 'Modification demandée',
    'accepted': 'Accepté',
    'refused': 'Refusé'
  };

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private http: HttpClient
  ) {}

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.loadQuote(id);
    }
  }

  loadQuote(id: string): void {
    this.http.get<any>(`http://localhost:8080/api/quotes/read_one.php?id=${id}`)
      .subscribe({
        next: (response) => {
          this.quote = response.data.quote;
          this.services = response.data.services || [];
          this.loading = false;
        },
        error: () => {
          this.error = 'Devis non trouvé';
          this.loading = false;
        }
      });
  }

  updateStatus(newStatus: string): void {
    this.http.put<any>('http://localhost:8080/api/quotes/update_status.php', {
      id: this.quote.id,
      status: newStatus
    }).subscribe({
      next: () => {
        this.quote.status = newStatus;
      },
      error: () => {
        alert('Erreur lors de la mise à jour');
      }
    });
  }

  downloadPdf(): void {
    window.open(`http://localhost:8080/api/quotes/generate_pdf.php?id=${this.quote.id}`, '_blank');
  }

  deleteQuote(): void {
    if (!confirm('Supprimer ce devis ?')) return;

    this.http.delete<any>('http://localhost:8080/api/quotes/delete.php', { body: { id: this.quote.id } })
      .subscribe({
        next: () => {
          this.router.navigate(['/admin/quotes']);
        },
        error: () => {
          alert('Erreur lors de la suppression');
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

  getStatusClass(status: string): string {
    return 'status-' + status;
  }
}