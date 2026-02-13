import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { EmployeeLayoutComponent } from '../../../components/employee-layout/employee-layout';

@Component({
  selector: 'app-employee-events',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, EmployeeLayoutComponent],
  template: `
    <app-employee-layout>
      <div class="page">
        <header class="page-header">
          <h1>🎉 Événements</h1>
          <div class="search-box">
            <input type="text" [(ngModel)]="searchTerm" (keyup.enter)="loadEvents()" placeholder="Rechercher...">
            <button type="button" (click)="loadEvents()" aria-label="Rechercher un événement"><span aria-hidden="true">🔍</span></button>
          </div>
        </header>
        <div *ngIf="loading" class="loading">Chargement...</div>
        <table *ngIf="!loading && events.length > 0" class="data-table">
          <thead>
            <tr><th>Nom</th><th>Client</th><th>Date</th><th>Lieu</th><th>Statut</th><th></th></tr>
          </thead>
          <tbody>
            <tr *ngFor="let e of filteredEvents">
              <td><strong>{{ e.name }}</strong></td>
              <td>{{ e.client_company || '-' }}</td>
              <td>{{ formatDate(e.start_date) }}</td>
              <td>{{ e.location || '-' }}</td>
              <td><span class="status-badge status-{{ e.status }}">{{ statusLabels[e.status] }}</span></td>
              <td><a [routerLink]="['/employee/events', e.id]" class="btn-view">Détails</a></td>
            </tr>
          </tbody>
        </table>
        <div *ngIf="!loading && events.length === 0" class="empty">Aucun événement</div>
      </div>
    </app-employee-layout>
  `,
  styleUrls: ['../clients/employee-clients.scss']
})
export class EmployeeEventsComponent implements OnInit {
  events: any[] = [];
  searchTerm = '';
  loading = true;
  statusLabels: any = { 'draft': 'Brouillon', 'client_review': 'En attente', 'accepted': 'Accepté', 'in_progress': 'En cours', 'completed': 'Terminé', 'cancelled': 'Annulé' };

  constructor(private http: HttpClient) {}
  ngOnInit(): void { this.loadEvents(); }

  loadEvents(): void {
    this.loading = true;
    this.http.get<any>('http://localhost:8080/api/events/read_all.php').subscribe({
      next: (r) => { this.events = r.data || []; this.loading = false; },
      error: () => { this.loading = false; }
    });
  }

  get filteredEvents() {
    if (!this.searchTerm) return this.events;
    const s = this.searchTerm.toLowerCase();
    return this.events.filter(e => e.name.toLowerCase().includes(s) || (e.client_company || '').toLowerCase().includes(s));
  }

  formatDate(d: string): string { return d ? new Date(d).toLocaleDateString('fr-FR') : '-'; }
}
