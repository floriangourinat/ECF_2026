import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';
import { AuthService } from '../../../_services/auth.service';

interface DashboardData {
  upcoming_events: any[];
  recent_notes: any[];
  global_notes: any[];
  stats: {
    active_clients: number;
    draft_events: number;
    prospects_to_contact: number;
    total_clients: number;
    total_events: number;
    pending_quotes: number;
    accepted_quotes: number;
  };
}

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, AdminLayoutComponent],
  templateUrl: './admin-dashboard.html',
  styleUrls: ['./admin-dashboard.scss']
})
export class AdminDashboardComponent implements OnInit {
  data: DashboardData | null = null;
  loading = true;
  error = '';

  newGlobalNote = '';
  savingGlobalNote = false;
  editingGlobalNoteId: number | null = null;
  editGlobalNoteContent = '';

  constructor(private http: HttpClient, private authService: AuthService) {}

  ngOnInit(): void {
    this.loadDashboard();
  }

  loadDashboard(): void {
    this.http.get<any>('http://localhost:8080/api/admin/dashboard.php')
      .subscribe({
        next: (response) => {
          this.data = response.data;
          this.loading = false;
        },
        error: () => {
          this.error = 'Impossible de charger le tableau de bord';
          this.loading = false;
        }
      });
  }

  addGlobalNote(): void {
    const content = this.newGlobalNote.trim();
    const currentUserId = Number(this.authService.currentUserValue?.id || 0);
    if (!content || !currentUserId) return;

    this.savingGlobalNote = true;
    this.http.post<any>('http://localhost:8080/api/notes/create.php', {
      author_id: currentUserId,
      content,
      is_global: true
    }).subscribe({
      next: (response) => {
        if (response?.success && this.data) {
          this.data.global_notes = [response.data, ...(this.data.global_notes || [])].slice(0, 10);
          this.data.recent_notes = [response.data, ...(this.data.recent_notes || [])].slice(0, 5);
          this.newGlobalNote = '';
        }
        this.savingGlobalNote = false;
      },
      error: () => {
        alert('Erreur lors de la création de la note globale');
        this.savingGlobalNote = false;
      }
    });
  }

  startEditGlobalNote(note: any): void {
    this.editingGlobalNoteId = note.id;
    this.editGlobalNoteContent = note.content;
  }

  cancelEditGlobalNote(): void {
    this.editingGlobalNoteId = null;
    this.editGlobalNoteContent = '';
  }

  saveEditGlobalNote(note: any): void {
    const content = this.editGlobalNoteContent.trim();
    if (!content) return;

    this.http.put<any>('http://localhost:8080/api/notes/update.php', {
      id: note.id,
      content
    }).subscribe({
      next: (response) => {
        if (response?.success) {
          note.content = content;
          this.syncRecentNoteContent(note.id, content);
          this.cancelEditGlobalNote();
          return;
        }
        alert(response?.message || 'Erreur lors de la mise à jour');
      },
      error: () => {
        alert('Erreur lors de la mise à jour');
      }
    });
  }

  deleteGlobalNote(note: any): void {
    if (!confirm('Supprimer cette note globale ?')) return;

    this.http.delete<any>('http://localhost:8080/api/notes/delete.php', { body: { id: note.id } }).subscribe({
      next: () => {
        if (!this.data) return;
        this.data.global_notes = (this.data.global_notes || []).filter(n => n.id !== note.id);
        this.data.recent_notes = (this.data.recent_notes || []).filter(n => n.id !== note.id);
      },
      error: () => {
        alert('Erreur lors de la suppression');
      }
    });
  }

  private syncRecentNoteContent(noteId: number, content: string): void {
    if (!this.data?.recent_notes) return;
    const target = this.data.recent_notes.find(n => Number(n.id) === Number(noteId));
    if (target) {
      target.content = content;
    }
  }

  formatDateTime(dateString: string): string {
    return new Date(dateString).toLocaleString('fr-FR', {
      day: 'numeric',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  getStatusLabel(status: string): string {
    const labels: { [key: string]: string } = {
      'draft': 'Brouillon',
      'client_review': 'En attente client',
      'accepted': 'Accepté',
      'in_progress': 'En cours',
      'completed': 'Terminé',
      'cancelled': 'Annulé'
    };
    return labels[status] || status;
  }

  getStatusClass(status: string): string {
    const classes: { [key: string]: string } = {
      'draft': 'status-draft',
      'client_review': 'status-pending',
      'accepted': 'status-accepted',
      'in_progress': 'status-progress',
      'completed': 'status-completed',
      'cancelled': 'status-cancelled'
    };
    return classes[status] || '';
  }
}
