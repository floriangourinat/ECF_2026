import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink, Router } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';
import { AuthService } from '../../../_services/auth.service';

@Component({
  selector: 'app-admin-event-detail',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, AdminLayoutComponent],
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

  // Pour ajouter une note
  newNoteContent = '';
  addingNote = false;

  // Image par défaut
  defaultImage = '/assets/images/event-default.jpg';

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
    private http: HttpClient,
    private authService: AuthService
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

  getEventImage(imagePath: string | null): string {
    if (imagePath && imagePath.trim() !== '') {
      if (imagePath.startsWith('/uploads/')) {
        return 'http://localhost:8080' + imagePath;
      }
      return imagePath;
    }
    return this.defaultImage;
  }

  onImageError(event: any): void {
    event.target.src = this.defaultImage;
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

  // ===== GESTION DES NOTES =====

  addNote(): void {
    if (!this.newNoteContent.trim()) {
      return;
    }

    const currentUser = this.authService.currentUserValue;
    if (!currentUser) {
      alert('Vous devez être connecté pour ajouter une note');
      return;
    }

    this.addingNote = true;

    this.http.post<any>('http://localhost:8080/api/notes/create.php', {
      event_id: this.event.id,
      author_id: currentUser.id,
      content: this.newNoteContent
    }).subscribe({
      next: (response) => {
        if (response.success) {
          this.notes.unshift(response.data);
          this.newNoteContent = '';
        }
        this.addingNote = false;
      },
      error: () => {
        alert('Erreur lors de l\'ajout de la note');
        this.addingNote = false;
      }
    });
  }

  deleteNote(note: any): void {
    if (!confirm('Supprimer cette note ?')) {
      return;
    }

    this.http.delete<any>('http://localhost:8080/api/notes/delete.php', { body: { id: note.id } })
      .subscribe({
        next: () => {
          this.notes = this.notes.filter(n => n.id !== note.id);
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