import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { EmployeeLayoutComponent } from '../../../components/employee-layout/employee-layout';
import { AuthService } from '../../../_services/auth.service';

@Component({
  selector: 'app-employee-event-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, EmployeeLayoutComponent],
  templateUrl: './employee-event-detail.html',
  styleUrls: ['./employee-event-detail.scss']
})
export class EmployeeEventDetailComponent implements OnInit {
  event: any = null;
  notes: any[] = [];
  tasks: any[] = [];
  loading = true;
  eventId = '';

  newNoteContent = '';
  addingNote = false;
  editingNoteId: number | null = null;
  editNoteContent = '';

  statusLabels: any = { 'draft': 'Brouillon', 'client_review': 'En attente', 'accepted': 'Accepté', 'in_progress': 'En cours', 'completed': 'Terminé', 'cancelled': 'Annulé' };
  taskStatusLabels: any = { 'todo': 'À faire', 'in_progress': 'En cours', 'done': 'Terminé' };

  constructor(private route: ActivatedRoute, private http: HttpClient, private authService: AuthService) {}

  ngOnInit(): void {
    this.eventId = this.route.snapshot.paramMap.get('id') || '';
    if (this.eventId) this.loadEvent();
  }

  get currentUser() { return this.authService.currentUserValue; }

  loadEvent(): void {
    this.http.get<any>(`http://localhost:8080/api/events/read_detail.php?id=${this.eventId}`).subscribe({
      next: (r) => {
        this.event = r.data.event;
        this.notes = r.data.notes || [];
        this.tasks = r.data.tasks || [];
        this.loading = false;
      },
      error: () => { this.loading = false; }
    });
  }

  // ===== NOTES CRUD (inchangé) =====
  addNote(): void {
    if (!this.newNoteContent.trim()) return;
    this.addingNote = true;
    this.http.post<any>('http://localhost:8080/api/notes/create.php', {
      event_id: this.event.id,
      author_id: this.currentUser?.id,
      content: this.newNoteContent
    }).subscribe({
      next: (r) => {
        if (r.success) { this.notes.unshift(r.data); this.newNoteContent = ''; }
        this.addingNote = false;
      },
      error: () => { this.addingNote = false; }
    });
  }

  startEditNote(note: any): void {
    this.editingNoteId = note.id;
    this.editNoteContent = note.content;
  }

  cancelEditNote(): void {
    this.editingNoteId = null;
    this.editNoteContent = '';
  }

  saveEditNote(note: any): void {
    if (!this.editNoteContent.trim()) return;
    this.http.put<any>('http://localhost:8080/api/notes/update.php', {
      id: note.id,
      content: this.editNoteContent
    }).subscribe({
      next: (r) => {
        if (r.success) { note.content = this.editNoteContent; }
        this.editingNoteId = null;
      }
    });
  }

  deleteNote(note: any): void {
    if (!confirm('Supprimer cette note ?')) return;
    this.http.delete<any>('http://localhost:8080/api/notes/delete.php', { body: { id: note.id } }).subscribe({
      next: () => { this.notes = this.notes.filter(n => n.id !== note.id); }
    });
  }

  isMyNote(note: any): boolean {
    return String(note.author_id) === String(this.currentUser?.id);
  }

  // ===== TÂCHES =====
  onAdvanceTask(task: any): void {
    const next = this.getNextStatus(task.status);
    if (!next) return;

    // POST au lieu de PUT pour éviter le blocage CORS preflight
    this.http.post<any>('http://localhost:8080/api/tasks/update_status.php', {
      id: Number(task.id),
      status: next,
      user_id: Number(this.currentUser?.id)
    }).subscribe({
      next: (r) => {
        if (r.success) {
          task.status = next;
        } else {
          alert(r.message || 'Impossible de mettre à jour');
        }
      },
      error: (err) => {
        alert(err.error?.message || 'Erreur serveur');
      }
    });
  }

  isMyTask(task: any): boolean {
    return String(task.assigned_to) === String(this.currentUser?.id);
  }

  getNextStatus(current: string): string | null {
    if (current === 'todo') return 'in_progress';
    if (current === 'in_progress') return 'done';
    return null;
  }

  formatDate(d: string): string {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
  }

  formatDateTime(d: string): string {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
  }
}
