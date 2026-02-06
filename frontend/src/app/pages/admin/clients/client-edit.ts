import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';

@Component({
  selector: 'app-client-edit',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, AdminLayoutComponent],
  templateUrl: './client-edit.html',
  styleUrls: ['./client-edit.scss']
})
export class ClientEditComponent implements OnInit {
  clientId: string = '';
  client: any = {
    id: '',
    first_name: '',
    last_name: '',
    email: '',
    company_name: '',
    phone: '',
    address: ''
  };
  loading = true;
  saving = false;
  error = '';
  success = '';

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private http: HttpClient
  ) {}

  ngOnInit(): void {
    this.clientId = this.route.snapshot.paramMap.get('id') || '';
    if (this.clientId) {
      this.loadClient();
    }
  }

  loadClient(): void {
    this.http.get<any>(`http://localhost:8080/api/clients/read_one.php?id=${this.clientId}`)
    .subscribe({
      next: (response) => {
        if (response.success && response.data && response.data.client) {
          const c = response.data.client;
          this.client = {
            id: c.id,
            first_name: c.first_name,
            last_name: c.last_name,
            email: c.email,
            company_name: c.company_name || '',
            phone: c.phone || '',
            address: c.address || ''
          };
        }
        this.loading = false;
      },
      error: () => {
        this.error = 'Client non trouvé';
        this.loading = false;
            }
        });
    }

  saveClient(): void {
    if (!this.client.first_name || !this.client.last_name || !this.client.email) {
      this.error = 'Prénom, nom et email sont requis';
      return;
    }

    this.saving = true;
    this.error = '';
    this.success = '';

    this.http.put<any>('http://localhost:8080/api/clients/update.php', this.client)
      .subscribe({
        next: (response) => {
          if (response.success) {
            this.success = 'Client modifié avec succès';
            setTimeout(() => {
              this.router.navigate(['/admin/clients', this.clientId]);
            }, 1500);
          } else {
            this.error = response.message || 'Erreur lors de la modification';
          }
          this.saving = false;
        },
        error: (err) => {
          this.error = err.error?.message || 'Erreur serveur';
          this.saving = false;
        }
      });
  }
}