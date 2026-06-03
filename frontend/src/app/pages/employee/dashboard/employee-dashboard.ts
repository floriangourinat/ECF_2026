import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { EmployeeLayoutComponent } from '../../../components/employee-layout/employee-layout';
import { AuthService } from '../../../_services/auth.service';

@Component({
  selector: 'app-employee-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink, EmployeeLayoutComponent],
  templateUrl: './employee-dashboard.html',
  styleUrls: ['./employee-dashboard.scss']
})
export class EmployeeDashboardComponent implements OnInit {
  myTasks: any[] = [];
  upcomingEvents: any[] = [];
  pendingReviews: any[] = [];
  loading = true;

  constructor(private http: HttpClient, private authService: AuthService) {}

  ngOnInit(): void {
    this.loadDashboard();
  }

  loadDashboard(): void {
    const userId = String(this.authService.currentUserValue?.id);

    // Toutes les tÃ¢ches, filtrÃ©es cÃ´tÃ© frontend par userId
    this.http.get<any>('/api/tasks/read.php').subscribe({
      next: (r) => {
        const allTasks = r.data || [];
        this.myTasks = allTasks
          .filter((t: any) => String(t.assigned_to) === userId && t.status !== 'done')
          .slice(0, 5);
      },
      error: () => {}
    });

    // Tous les Ã©vÃ©nements non terminÃ©s/annulÃ©s
    this.http.get<any>('/api/events/read_all.php').subscribe({
      next: (r) => {
        this.upcomingEvents = (r.data || [])
          .filter((e: any) => e.status !== 'cancelled' && e.status !== 'completed')
          .slice(0, 3);
        this.loading = false;
      },
      error: () => { this.loading = false; }
    });

    // Avis en attente
    this.http.get<any>('/api/reviews/read_all.php').subscribe({
      next: (r) => {
        this.pendingReviews = (r.data || []).filter((rev: any) => rev.status === 'pending');
      },
      error: () => {}
    });
  }

  get currentUser() { return this.authService.currentUserValue; }

  formatDate(d: string): string {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  taskStatusLabels: any = { 'todo': 'Ã€ faire', 'in_progress': 'En cours', 'done': 'TerminÃ©' };
}
