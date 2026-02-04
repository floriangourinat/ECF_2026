import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';

interface Employee {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  username: string;
  role: string;
  is_active: boolean;
  created_at: string;
}

@Component({
  selector: 'app-employees-list',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, AdminLayoutComponent],
  templateUrl: './employees-list.html',
  styleUrls: ['./employees-list.scss']
})
export class EmployeesListComponent implements OnInit {
  employees: Employee[] = [];
  loading = true;
  error = '';
  searchTerm = '';
  filterRole = '';

  showCreateModal = false;
  createLoading = false;
  createError = '';
  newEmployee = {
    email: '',
    last_name: '',
    first_name: '',
    username: '',
    role: 'employee'
  };

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
    this.loadEmployees();
  }

  loadEmployees(): void {
    this.loading = true;
    let url = 'http://localhost:8080/api/employees/read.php?';
    
    if (this.filterRole) {
      url += `role=${this.filterRole}&`;
    }
    if (this.searchTerm) {
      url += `search=${encodeURIComponent(this.searchTerm)}`;
    }

    this.http.get<any>(url).subscribe({
      next: (response) => {
        this.employees = response.data || [];
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les employés';
        this.loading = false;
      }
    });
  }

  onSearch(): void {
    this.loadEmployees();
  }

  onFilterChange(): void {
    this.loadEmployees();
  }

  openCreateModal(): void {
    this.showCreateModal = true;
    this.createError = '';
    this.newEmployee = {
      email: '',
      last_name: '',
      first_name: '',
      username: '',
      role: 'employee'
    };
  }

  closeCreateModal(): void {
    this.showCreateModal = false;
  }

  createEmployee(): void {
    if (!this.newEmployee.email || !this.newEmployee.last_name || !this.newEmployee.first_name) {
      this.createError = 'Email, nom et prénom sont requis';
      return;
    }

    this.createLoading = true;
    this.createError = '';

    this.http.post<any>('http://localhost:8080/api/employees/create.php', this.newEmployee)
      .subscribe({
        next: (response) => {
          alert(`Employé créé avec succès !\n\nMot de passe temporaire : ${response.data.temp_password}`);
          this.closeCreateModal();
          this.loadEmployees();
          this.createLoading = false;
        },
        error: (err) => {
          this.createError = err.error?.message || 'Erreur lors de la création';
          this.createLoading = false;
        }
      });
  }

  toggleStatus(employee: Employee): void {
    const action = employee.is_active ? 'désactiver' : 'activer';
    if (!confirm(`Voulez-vous ${action} ${employee.first_name} ${employee.last_name} ?`)) {
      return;
    }

    this.http.put<any>('http://localhost:8080/api/employees/toggle_status.php', { id: employee.id })
      .subscribe({
        next: (response) => {
          employee.is_active = response.data.is_active;
        },
        error: () => {
          alert('Erreur lors de la modification du statut');
        }
      });
  }

  deleteEmployee(employee: Employee): void {
    if (!confirm(`Supprimer définitivement ${employee.first_name} ${employee.last_name} ?`)) {
      return;
    }

    this.http.delete<any>('http://localhost:8080/api/employees/delete.php', { body: { id: employee.id } })
      .subscribe({
        next: () => {
          this.employees = this.employees.filter(e => e.id !== employee.id);
        },
        error: (err) => {
          alert(err.error?.message || 'Erreur lors de la suppression');
        }
      });
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('fr-FR');
  }

  getRoleLabel(role: string): string {
    return role === 'admin' ? 'Administrateur' : 'Employé';
  }
}