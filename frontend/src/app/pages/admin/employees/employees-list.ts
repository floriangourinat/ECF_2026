import { Component, ElementRef, HostListener, OnInit } from '@angular/core';
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
  private previousFocusedElement: HTMLElement | null = null;
  newEmployee = {
    email: '',
    last_name: '',
    first_name: '',
    username: '',
    role: 'employee'
  };

  constructor(
    private http: HttpClient,
    private elementRef: ElementRef<HTMLElement>
  ) {}

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
    this.previousFocusedElement = document.activeElement as HTMLElement;
    this.showCreateModal = true;
    this.createError = '';
    this.newEmployee = {
      email: '',
      last_name: '',
      first_name: '',
      username: '',
      role: 'employee'
    };

    setTimeout(() => {
      const firstField = this.elementRef.nativeElement.querySelector<HTMLElement>('#new-employee-first-name');
      firstField?.focus();
    });
  }

  closeCreateModal(): void {
    this.showCreateModal = false;
    this.previousFocusedElement?.focus();
  }

  @HostListener('document:keydown', ['$event'])
  onDocumentKeydown(event: KeyboardEvent): void {
    if (!this.showCreateModal) return;

    if (event.key === 'Escape') {
      event.preventDefault();
      this.closeCreateModal();
      return;
    }

    if (event.key !== 'Tab') return;

    const modal = this.elementRef.nativeElement.querySelector<HTMLElement>('.modal');
    if (!modal) return;

    const focusable = modal.querySelectorAll<HTMLElement>(
      'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
    );

    if (focusable.length === 0) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    const active = document.activeElement as HTMLElement;

    if (event.shiftKey && active === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && active === last) {
      event.preventDefault();
      first.focus();
    }
  }

  createEmployee(): void {
    if (!this.newEmployee.email || !this.newEmployee.last_name || !this.newEmployee.first_name) {
      this.createError = 'Email, nom et prénom sont requis';
      return;
    }

    this.createLoading = true;
    this.createError = '';

    this.http.post<any>('http://localhost:8080/api/employees/create.php', { ...this.newEmployee, role: 'employee' })
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
