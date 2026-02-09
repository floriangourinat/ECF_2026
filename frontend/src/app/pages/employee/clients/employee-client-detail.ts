import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { EmployeeLayoutComponent } from '../../../components/employee-layout/employee-layout';

@Component({
  selector: 'app-employee-client-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, EmployeeLayoutComponent],
  template: `
    <app-employee-layout>
      <div class="page">
        <a routerLink="/employee/clients" class="btn-back">← Retour aux clients</a>
        <div *ngIf="loading" class="loading">Chargement...</div>
        <div *ngIf="!loading && client" class="detail">
          <header class="detail-header">
            <h1>{{ client.company_name || client.first_name + ' ' + client.last_name }}</h1>
          </header>
          <div class="info-grid">
            <div class="info-card">
              <h3>📋 Informations</h3>
              <p><strong>Contact :</strong> {{ client.first_name }} {{ client.last_name }}</p>
              <p><strong>Email :</strong> {{ client.email }}</p>
              <p><strong>Téléphone :</strong> {{ client.phone || '-' }}</p>
              <p><strong>Adresse :</strong> {{ client.address || '-' }}</p>
            </div>
          </div>
          <section class="section">
            <h3>🎉 Événements du client</h3>
            <div *ngIf="events.length === 0" class="empty">Aucun événement</div>
            <table *ngIf="events.length > 0" class="data-table">
              <thead><tr><th>Nom</th><th>Date</th><th>Lieu</th><th>Statut</th><th></th></tr></thead>
              <tbody>
                <tr *ngFor="let e of events">
                  <td><strong>{{ e.name }}</strong></td>
                  <td>{{ formatDate(e.start_date) }}</td>
                  <td>{{ e.location || '-' }}</td>
                  <td><span class="badge">{{ statusLabels[e.status] || e.status }}</span></td>
                  <td><a [routerLink]="['/employee/events', e.id]" class="btn-view">Voir</a></td>
                </tr>
              </tbody>
            </table>
          </section>
        </div>
      </div>
    </app-employee-layout>
  `,
  styleUrls: ['./employee-clients.scss']
})
export class EmployeeClientDetailComponent implements OnInit {
  client: any = null;
  events: any[] = [];
  loading = true;

  statusLabels: any = { 'draft': 'Brouillon', 'client_review': 'En attente', 'accepted': 'Accepté', 'in_progress': 'En cours', 'completed': 'Terminé', 'cancelled': 'Annulé' };

  constructor(private route: ActivatedRoute, private http: HttpClient) {}

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) this.loadClient(id);
  }

  loadClient(id: string): void {
    this.http.get<any>(`http://localhost:8080/api/clients/read_one.php?id=${id}`).subscribe({
      next: (r) => {
        this.client = r.data;
        this.events = r.data?.events || [];
        this.loading = false;
      },
      error: () => { this.loading = false; }
    });
  }

  formatDate(d: string): string {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('fr-FR');
  }
}
