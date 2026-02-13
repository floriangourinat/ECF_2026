import { Component, ElementRef, HostListener, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';

interface Client {
  id: number;
  company_name: string;
  phone: string;
  address: string;
  user_id: number;
  first_name: string;
  last_name: string;
  email: string;
  is_active: boolean;
  created_at: string;
}

@Component({
  selector: 'app-clients-list',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, AdminLayoutComponent],
  templateUrl: './clients-list.html',
  styleUrls: ['./clients-list.scss']
})
export class ClientsListComponent implements OnInit {
  clients: Client[] = [];
  loading = true;
  error = '';
  searchTerm = '';
  filterActive = '';
  
  showCreateModal = false;
  newClient = {
    email: '',
    last_name: '',
    first_name: '',
    company_name: '',
    phone: '',
    address: ''
  };
  createLoading = false;
  createError = '';
  private previousFocusedElement: HTMLElement | null = null;

  constructor(
    private http: HttpClient,
    private elementRef: ElementRef<HTMLElement>
  ) {}

  ngOnInit(): void {
    this.loadClients();
  }

  loadClients(): void {
    this.loading = true;
    let url = 'http://localhost:8080/api/clients/read.php?';
    
    if (this.filterActive !== '') {
      url += `is_active=${this.filterActive}&`;
    }
    if (this.searchTerm) {
      url += `search=${encodeURIComponent(this.searchTerm)}`;
    }

    this.http.get<any>(url).subscribe({
      next: (response) => {
        this.clients = response.data || [];
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les clients';
        this.loading = false;
      }
    });
  }

  onSearch(): void {
    this.loadClients();
  }

  onFilterChange(): void {
    this.loadClients();
  }

  openCreateModal(): void {
    this.previousFocusedElement = document.activeElement as HTMLElement;
    this.showCreateModal = true;
    this.createError = '';
    this.newClient = {
      email: '',
      last_name: '',
      first_name: '',
      company_name: '',
      phone: '',
      address: ''
    };

    setTimeout(() => {
      const firstField = this.elementRef.nativeElement.querySelector<HTMLElement>('#new-client-first-name');
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

  createClient(): void {
    if (!this.newClient.email || !this.newClient.last_name || !this.newClient.first_name) {
      this.createError = 'Email, nom et prénom sont requis';
      return;
    }

    this.createLoading = true;
    this.createError = '';

    this.http.post<any>('http://localhost:8080/api/clients/create.php', this.newClient)
      .subscribe({
        next: (response) => {
          alert(`Client créé avec succès !\n\nMot de passe temporaire : ${response.data.temp_password}`);
          this.closeCreateModal();
          this.loadClients();
          this.createLoading = false;
        },
        error: (err) => {
          this.createError = err.error?.message || 'Erreur lors de la création';
          this.createLoading = false;
        }
      });
  }

  toggleStatus(client: Client): void {
    const action = client.is_active ? 'suspendre' : 'activer';
    if (!confirm(`Voulez-vous ${action} ce client ?`)) {
      return;
    }

    this.http.put<any>('http://localhost:8080/api/clients/toggle_status.php', { id: client.id })
      .subscribe({
        next: (response) => {
          client.is_active = response.data.is_active;
        },
        error: () => {
          alert('Erreur lors de la modification du statut');
        }
      });
  }

  deleteClient(client: Client): void {
    if (!confirm(`Supprimer définitivement ${client.company_name || client.first_name + ' ' + client.last_name} ?\n\nCette action supprimera également tous les événements et devis associés.`)) {
      return;
    }

    this.http.delete<any>('http://localhost:8080/api/clients/delete.php', { body: { id: client.id } })
      .subscribe({
        next: () => {
          this.clients = this.clients.filter(c => c.id !== client.id);
        },
        error: () => {
          alert('Erreur lors de la suppression');
        }
      });
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('fr-FR');
  }
}
