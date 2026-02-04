import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink, Router } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';

@Component({
  selector: 'app-admin-event-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, AdminLayoutComponent],
  templateUrl: './event-detail.html',
  styleUrls: ['./event-detail.scss']
})
export class AdminEventDetailComponent implements OnInit {
  event: any = null;
  quotes: any[] = [];
  notes: any[] = [];
  tasks: any[] = [];
  loading = true;
  error = '';

  statusLabels: { [key: string]: string } = {
    'draft': 'Brouillon',
    'client_review': 'En attente client',
    'accepted': 'Accepté',
    'in_progress': 'En cours',
    'completed': 'Terminé',
    'cancelled': 'Annulé'
  };

  taskStatusLabels: { [key: string]: string } = {
    'todo': 'À faire',
    'in_progress': 'En cours',
    'done': 'Terminé'
  };

  quoteStatusLabels: { [key: string]: string } = {
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
      this.loadEvent(id);
    }
  }

  loadEvent(id: string): void {
    this.http.get<any>(`http://localhost:8080/api/events/read_detail.php?id=${id}`)
      .subscribe({
        next: (response) => {
          this.event = response.data.event;
          this.quotes = response.data.quotes || [];
          this.notes = response.data.notes || [];
          this.tasks = response.data.tasks || [];
          this.loading = false;
        },
        error: () => {
          this.error = 'Événement non trouvé';
          this.loading = false;
        }
      });
  }

  updateStatus(newStatus: string): void {
    this.http.put<any>('http://localhost:8080/api/events/update.php', {
      ...this.event,
      status: newStatus
    }).subscribe({
      next: () => {
        this.event.status = newStatus;
      },
      error: () => {
        alert('Erreur lors de la mise à jour du statut');
      }
    });
  }

  deleteEvent(): void {
    if (!confirm(`Supprimer l'événement "${this.event.name}" ?\n\nCette action est irréversible.`)) {
      return;
    }

    this.http.delete<any>('http://localhost:8080/api/events/delete.php', { body: { id: this.event.id } })
      .subscribe({
        next: () => {
          this.router.navigate(['/admin/events']);
        },
        error: () => {
          alert('Erreur lors de la suppression');
        }
      });
  }

  formatDate(dateString: string): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  formatCurrency(amount: number): string {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
  }

  getStatusClass(status: string): string {
    return 'status-' + status.replace('_', '-');
  }
}