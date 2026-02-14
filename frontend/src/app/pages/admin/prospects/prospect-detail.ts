import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
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
  selector: 'app-prospect-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, AdminLayoutComponent],
  templateUrl: './prospect-detail.html',
  styleUrls: ['./prospect-detail.scss']
})
export class ProspectDetailComponent implements OnInit {
  prospect: Prospect | null = null;
  loading = true;
  error = '';

  statusLabels: { [key: string]: string } = {
    'to_contact': 'À contacter',
    'qualification': 'Qualification',
    'failed': 'Échoué',
    'converted': 'Converti'
  };

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private http: HttpClient
  ) {}

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.loadProspect(id);
    }
  }

  loadProspect(id: string): void {
    this.http.get<any>(`http://localhost:8080/api/prospects/read_one.php?id=${id}`)
      .subscribe({
        next: (response) => {
          this.prospect = response.data;
          this.loading = false;
        },
        error: () => {
          this.error = 'Prospect non trouvé';
          this.loading = false;
        }
      });
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

  updateStatus(newStatus: string): void {
    if (!this.prospect) return;

    let failureMessage: string | null = null;

    if (newStatus === 'failed') {
      failureMessage = prompt('Message à envoyer au prospect (obligatoire) :', 'Après qualification, votre besoin n’est pas réalisable dans les délais demandés.');
      if (!failureMessage || !failureMessage.trim()) {
        alert('Le message d’échec est obligatoire.');
        return;
      }
    }

    this.http.put<any>('http://localhost:8080/api/prospects/update_status.php', {
      id: this.prospect.id,
      status: newStatus,
      failure_message: failureMessage
    }).subscribe({
      next: () => {
        if (this.prospect) {
          this.prospect.status = newStatus;
        }
      },
      error: (err) => {
        alert(err.error?.message || 'Erreur lors de la mise à jour du statut');
      }
    });
  }

  convertToClient(): void {
    if (!this.prospect) return;

    if (!confirm(`Convertir ${this.prospect.company_name} en client ?`)) {
      return;
    }

    this.http.post<any>('http://localhost:8080/api/prospects/convert.php', {
      prospect_id: this.prospect.id
    }).subscribe({
      next: (response) => {
        const clientId = response?.data?.client_id;

        if (response?.data?.already_existing) {
          alert('Un utilisateur avec cet email existe déjà. Le prospect est marqué comme converti.');
        } else {
          alert(`Client créé avec succès !\n\nMot de passe temporaire : ${response.data.temp_password}\n\nCommuniquez ce mot de passe au client.`);
        }

        if (this.prospect) {
          this.prospect.status = 'converted';
        }

        if (clientId && this.prospect) {
          this.router.navigate(['/admin/events'], {
            queryParams: {
              from_prospect: 1,
              client_id: clientId,
              company_name: this.prospect.company_name || '',
              first_name: this.prospect.first_name || '',
              last_name: this.prospect.last_name || '',
              event_type: this.prospect.event_type || '',
              location: this.prospect.location || '',
              planned_date: this.prospect.planned_date || ''
            }
          });
        }
      },
      error: (err) => {
        alert(err.error?.message || 'Erreur lors de la conversion');
      }
    });
  }

  createPrefilledEvent(): void {
    if (!this.prospect) return;

    this.router.navigate(['/admin/events'], {
      queryParams: {
        from_prospect: 1,
        company_name: this.prospect.company_name || '',
        first_name: this.prospect.first_name || '',
        last_name: this.prospect.last_name || '',
        event_type: this.prospect.event_type || '',
        location: this.prospect.location || '',
        planned_date: this.prospect.planned_date || ''
      }
    });
  }

  decodeText(value: string | null | undefined): string {
    if (!value) return '-';

    const textarea = document.createElement('textarea');
    textarea.innerHTML = value;
    return textarea.value;
  }

  formatDate(dateString: string): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    });
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
