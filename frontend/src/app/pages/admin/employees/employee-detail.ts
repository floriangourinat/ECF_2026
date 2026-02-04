import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';

@Component({
  selector: 'app-employee-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, AdminLayoutComponent],
  templateUrl: './employee-detail.html',
  styleUrls: ['./employee-detail.scss']
})
export class EmployeeDetailComponent implements OnInit {
  employee: any = null;
  tasks: any[] = [];
  notes: any[] = [];
  loading = true;
  error = '';

  taskStatusLabels: { [key: string]: string } = {
    'todo': 'À faire',
    'in_progress': 'En cours',
    'done': 'Terminé'
  };

  constructor(
    private route: ActivatedRoute,
    private http: HttpClient
  ) {}

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.loadEmployee(id);
    }
  }

  loadEmployee(id: string): void {
    this.http.get<any>(`http://localhost:8080/api/employees/read_one.php?id=${id}`)
      .subscribe({
        next: (response) => {
          this.employee = response.data.employee;
          this.tasks = response.data.tasks || [];
          this.notes = response.data.notes || [];
          this.loading = false;
        },
        error: () => {
          this.error = 'Employé non trouvé';
          this.loading = false;
        }
      });
  }

  formatDate(dateString: string): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR');
  }

  formatDateTime(dateString: string): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('fr-FR');
  }

  getRoleLabel(role: string): string {
    return role === 'admin' ? 'Administrateur' : 'Employé';
  }
}