import { Component, HostListener, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';

interface Prospect {
  id: number;
  company_name: string;
  last_name: string;
  first_name: string;
  email: string;
  phone: string;
  location: string;
  event_type: string;
  planned_date: string;
  estimated_participants: number;
  needs_description: string;
  image_path: string;
  status: string;
  created_at: string;
}

@Component({
  selector: 'app-prospects-list',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, AdminLayoutComponent],
  templateUrl: './prospects-list.html',
  styleUrls: ['./prospects-list.scss']
})
export class ProspectsListComponent implements OnInit {
  prospects: Prospect[] = [];
  loading = true;
  error = '';
  searchTerm = '';
  filterStatus = '';
  openDropdownProspectId: number | null = null;

  statusLabels: { [key: string]: string } = {
    'to_contact': 'À contacter',
    'qualification': 'Qualification',
    'failed': 'Échoué',
    'converted': 'Converti'
  };

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
    this.loadProspects();
  }

  loadProspects(): void {
    this.loading = true;
    let url = 'http://localhost:8080/api/prospects/read.php?';

    if (this.filterStatus) {
      url += `status=${this.filterStatus}&`;
    }
    if (this.searchTerm) {
      url += `search=${encodeURIComponent(this.searchTerm)}`;
    }

    this.http.get<any>(url).subscribe({
      next: (response) => {
        this.prospects = response.data || [];
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les prospects';
        this.loading = false;
      }
    });
  }

  onSearch(): void {
    this.loadProspects();
  }

  onFilterChange(): void {
    this.loadProspects();
  }

  toggleDropdown(prospectId: number, event: MouseEvent): void {
    event.stopPropagation();
    this.openDropdownProspectId = this.openDropdownProspectId === prospectId ? null : prospectId;
  }

  isDropdownOpen(prospectId: number): boolean {
    return this.openDropdownProspectId === prospectId;
  }

  closeDropdown(): void {
    this.openDropdownProspectId = null;
  }

  @HostListener('document:click')
  onDocumentClick(): void {
    this.closeDropdown();
  }

  getImageUrl(path: string): string {
    if (path && path.startsWith('/uploads/')) {
      return 'http://localhost:8080' + path;
    }
    return path;
  }

  onImageError(event: any): void {
    event.target.style.display = 'none';
  }

  updateStatus(prospect: Prospect, newStatus: string): void {
    let failureMessage: string | null = null;

    if (newStatus === 'failed') {
      failureMessage = prompt('Message à envoyer au prospect (obligatoire) :', 'Après qualification, votre besoin n’est pas réalisable dans les délais demandés.');
      if (!failureMessage || !failureMessage.trim()) {
        alert('Le message d’échec est obligatoire.');
        return;
      }
    }

    this.http.put<any>('http://localhost:8080/api/prospects/update_status.php', {
      id: prospect.id,
      status: newStatus,
      failure_message: failureMessage
    }).subscribe({
      next: () => {
        prospect.status = newStatus;
        this.closeDropdown();
      },
      error: (err) => {
        alert(err.error?.message || 'Erreur lors de la mise à jour du statut');
      }
    });
  }

  convertToClient(prospect: Prospect): void {
    if (!confirm(`Convertir ${prospect.company_name} en client ?`)) {
      return;
    }

    this.http.post<any>('http://localhost:8080/api/prospects/convert.php', {
      prospect_id: prospect.id
    }).subscribe({
      next: (response) => {
        if (response?.data?.already_existing) {
          alert('Compte existant trouvé pour cet email. Le prospect est marqué comme converti.');
        } else {
          alert(`Client créé avec succès !\nMot de passe temporaire : ${response.data.temp_password}`);
        }
        prospect.status = 'converted';
        this.closeDropdown();
      },
      error: (err) => {
        alert(err.error?.message || 'Erreur lors de la conversion');
      }
    });
  }

  formatDate(dateString: string): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR');
  }

  getStatusClass(status: string): string {
    const classes: { [key: string]: string } = {
      'to_contact': 'status-to-contact',
      'qualification': 'status-qualification',
      'failed': 'status-failed',
      'converted': 'status-converted'
    };
    return classes[status] || '';
  }
}
