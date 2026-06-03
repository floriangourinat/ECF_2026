import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
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
  employees: any[] = [];
  loading = true;
  error = '';

  // Notes
  newNoteContent = '';
  addingNote = false;
  editingNoteId: number | null = null;
  editNoteContent = '';

  // TÃ¢ches
  showTaskModal = false;
  taskLoading = false;
  taskError = '';
  newTask = { title: '', description: '', assigned_to: '', due_date: '' };

  @ViewChild('taskTitleInput') taskTitleInput?: ElementRef<HTMLInputElement>;

  defaultImage = 'assets/images/event-default.jpg';

  statusLabels: { [key: string]: string } = {
    'draft': 'Brouillon', 'client_review': 'En attente client', 'accepted': 'AcceptÃ©',
    'in_progress': 'En cours', 'completed': 'TerminÃ©', 'cancelled': 'AnnulÃ©'
  };
  taskStatusLabels: { [key: string]: string } = {
    'todo': 'Ã€ faire', 'in_progress': 'En cours', 'done': 'TerminÃ©'
  };
  quoteStatusLabels: { [key: string]: string } = {
    'pending': 'En attente', 'modification': 'Modification demandÃ©e', 'accepted': 'AcceptÃ©', 'refused': 'RefusÃ©'
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
      this.loadEmployees();
    }
  }

  loadEvent(id: string): void {
    this.http.get<any>(`/api/events/read_detail.php?id=${id}`)
      .subscribe({
        next: (response) => {
          this.event = response.data.event;
          this.quotes = response.data.quotes || [];
          this.notes = response.data.notes || [];
          this.tasks = response.data.tasks || [];
          this.loading = false;
        },
        error: () => {
          this.error = 'Ã‰vÃ©nement non trouvÃ©';
          this.loading = false;
        }
      });
  }

  loadEmployees(): void {
    this.http.get<any>('/api/employees/read.php').subscribe({
      next: (r) => { this.employees = r.data || []; }
    });
  }

  getEventImage(imagePath: string | null): string {
    if (imagePath && imagePath.trim() !== '') {
      if (imagePath.startsWith('/uploads/')) return '/api' + imagePath;
      return imagePath;
    }
    return this.defaultImage;
  }

  onImageError(event: any): void { event.target.src = this.defaultImage; }

  updateStatus(newStatus: string): void {
    this.http.put<any>('/api/events/update.php', {
      ...this.event, status: newStatus
    }).subscribe({
      next: () => { this.event.status = newStatus; },
      error: () => { alert('Erreur lors de la mise Ã  jour du statut'); }
    });
  }

  deleteEvent(): void {
    if (!confirm(`Supprimer l'Ã©vÃ©nement "${this.event.name}" ?\n\nCette action est irrÃ©versible.`)) return;
    this.http.delete<any>('/api/events/delete.php', { body: { id: this.event.id } })
      .subscribe({
        next: () => { this.router.navigate(['/admin/events']); },
        error: () => { alert('Erreur lors de la suppression'); }
      });
  }

  // ===== TÃ‚CHES =====

  openTaskModal(): void {
    this.showTaskModal = true;
    this.taskError = '';
    this.newTask = { title: '', description: '', assigned_to: '', due_date: '' };

    setTimeout(() => {
      this.taskTitleInput?.nativeElement.focus();
    });
  }

  closeTaskModal(): void {
    this.showTaskModal = false;
  }

  createTask(): void {
    if (!this.newTask.title.trim()) {
      this.taskError = 'Le titre est requis';
      return;
    }
    this.taskLoading = true;
    this.taskError = '';

    const payload: any = {
      event_id: this.event.id,
      title: this.newTask.title,
      description: this.newTask.description || null,
      assigned_to: this.newTask.assigned_to || null,
      due_date: this.newTask.due_date || null,
      status: 'todo'
    };

    this.http.post<any>('/api/tasks/create.php', payload).subscribe({
      next: (r) => {
        if (r.success) {
          this.tasks.push(r.data);
          this.closeTaskModal();
        } else {
          this.taskError = r.message || 'Erreur';
        }
        this.taskLoading = false;
      },
      error: (err) => {
        this.taskError = err.error?.message || 'Erreur serveur';
        this.taskLoading = false;
      }
    });
  }

  deleteTask(task: any): void {
    if (!confirm(`Supprimer la tÃ¢che "${task.title}" ?`)) return;
    this.http.delete<any>('/api/tasks/delete.php', { body: { id: task.id } }).subscribe({
      next: () => { this.tasks = this.tasks.filter(t => t.id !== task.id); },
      error: () => { alert('Erreur lors de la suppression'); }
    });
  }

  // ===== NOTES =====

  addNote(): void {
    if (!this.newNoteContent.trim()) return;
    const currentUser = this.authService.currentUserValue;
    if (!currentUser) { alert('Vous devez Ãªtre connectÃ©'); return; }

    this.addingNote = true;
    this.http.post<any>('/api/notes/create.php', {
      event_id: this.event.id,
      author_id: currentUser.id,
      content: this.newNoteContent
    }).subscribe({
      next: (r) => {
        if (r.success) { this.notes.unshift(r.data); this.newNoteContent = ''; }
        this.addingNote = false;
      },
      error: () => { alert('Erreur lors de l\'ajout'); this.addingNote = false; }
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
    this.http.put<any>('/api/notes/update.php', {
      id: note.id,
      content: this.editNoteContent
    }).subscribe({
      next: (r) => {
        if (r.success) {
          note.content = this.editNoteContent;
          this.cancelEditNote();
          return;
        }
        alert(r.message || 'Erreur lors de la modification');
      },
      error: () => { alert('Erreur lors de la modification'); }
    });
  }

  deleteNote(note: any): void {
    if (!confirm('Supprimer cette note ?')) return;
    this.http.delete<any>('/api/notes/delete.php', { body: { id: note.id } }).subscribe({
      next: () => { this.notes = this.notes.filter(n => n.id !== note.id); },
      error: () => { alert('Erreur lors de la suppression'); }
    });
  }

  formatDate(dateString: string): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
  }

  formatCurrency(amount: number): string {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
  }

  getStatusClass(status: string): string {
    return 'status-' + status.replace('_', '-');
  }
}
