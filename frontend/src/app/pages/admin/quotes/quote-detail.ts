import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../../_services/auth.service';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';

@Component({
  selector: 'app-quote-detail',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, AdminLayoutComponent],
  templateUrl: './quote-detail.html',
  styleUrls: ['./quote-detail.scss']
})
export class QuoteDetailComponent implements OnInit {
  quote: any = null;
  services: any[] = [];
  loading = true;
  error = '';
  sending = false;

  counterProposalText = '';
  sendingCounterProposal = false;

  private readonly counterProposalMarker = '[CONTREPROPOSITION_INNOV_EVENTS]';

  statusLabels: { [key: string]: string } = {
    'draft': 'Brouillon',
    'pending': 'Étude côté client',
    'modification': 'Modification demandée',
    'accepted': 'Accepté',
    'refused': 'Refusé'
  };

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private http: HttpClient,
    private authService: AuthService
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

  sendCounterProposal(): void {
    if (!this.quote?.id) return;
    const content = this.counterProposalText.trim();
    if (!content) {
      alert('Veuillez saisir une contreproposition.');
      return;
    }

    const adminUserId = Number(this.authService.currentUserValue?.id || 0);
    if (!adminUserId) {
      alert('Utilisateur non connecté');
      return;
    }

    this.sendingCounterProposal = true;
    this.http.post<any>('http://localhost:8080/api/quotes/admin_counter_proposal.php', {
      quote_id: this.quote.id,
      admin_user_id: adminUserId,
      counter_proposal: content
    }).subscribe({
      next: (response) => {
        this.quote.status = response?.data?.status || 'pending';
        this.quote.modification_reason = response?.data?.modification_reason || this.quote.modification_reason;
        this.quote.counter_proposal = response?.data?.counter_proposal || this.quote.counter_proposal;
        this.quote.counter_proposed_at = response?.data?.counter_proposed_at || this.quote.counter_proposed_at;
        this.counterProposalText = '';
        this.sendingCounterProposal = false;
        alert(response?.message || 'Contreproposition envoyée');
      },
      error: (err) => {
        alert(err?.error?.message || 'Erreur lors de l\'envoi de la contreproposition');
        this.sendingCounterProposal = false;
      }
    });
  }

  downloadPdf(): void {
    window.open(`http://localhost:8080/api/quotes/generate_pdf.php?id=${this.quote.id}`, '_blank');
  }

  sendByEmail(): void {
    if (!confirm('Envoyer ce devis par email au client ?')) return;

    this.sending = true;
    this.http.post<any>('http://localhost:8080/api/quotes/send_email.php', {
      quote_id: this.quote.id
    }).subscribe({
      next: (response) => {
        this.quote.status = response?.data?.status || 'pending';
        alert(response.message || 'Email envoyé avec succès !');
        this.sending = false;
      },
      error: (err) => {
        alert(err.error?.message || 'Erreur lors de l\'envoi');
        this.sending = false;
      }
    });
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

  getClientModificationReason(rawReason?: string): string {
    const source = (rawReason || '').trim();
    if (!source.includes(this.counterProposalMarker)) {
      return source;
    }

    return source.split(this.counterProposalMarker)[0].trim();
  }

  getCounterProposal(rawReason?: string): string {
    const structured = (this.quote?.counter_proposal || '').trim();
    if (structured) {
      return structured;
    }

    const source = (rawReason || '').trim();
    if (!source.includes(this.counterProposalMarker)) {
      return '';
    }

    const chunk = source.split(this.counterProposalMarker)[1] || '';
    const messageMatch = chunk.match(/Message:\s*([\s\S]*)$/);
    return messageMatch?.[1]?.trim() || '';
  }

  getCounterProposalDate(rawReason?: string): string {
    if (this.quote?.counter_proposed_at) {
      return this.formatDate(this.quote.counter_proposed_at);
    }

    const source = (rawReason || '').trim();
    if (!source.includes(this.counterProposalMarker)) {
      return '';
    }

    const chunk = source.split(this.counterProposalMarker)[1] || '';
    const dateMatch = chunk.match(/Date:\s*([^\n]+)/);
    return dateMatch?.[1]?.trim() || '';
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
