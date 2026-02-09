import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { EmployeeLayoutComponent } from '../../../components/employee-layout/employee-layout';

@Component({
  selector: 'app-employee-clients',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, EmployeeLayoutComponent],
  template: `
    <app-employee-layout>
      <div class="page">
        <header class="page-header">
          <h1>👥 Clients</h1>
          <div class="search-box">
            <input type="text" [(ngModel)]="searchTerm" (keyup.enter)="loadClients()" placeholder="Rechercher un client...">
            <button (click)="loadClients()">🔍</button>
          </div>
        </header>
        <div *ngIf="loading" class="loading">Chargement...</div>
        <table *ngIf="!loading && clients.length > 0" class="data-table">
          <thead>
            <tr><th>Entreprise</th><th>Contact</th><th>Email</th><th>Événements</th><th></th></tr>
          </thead>
          <tbody>
            <tr *ngFor="let c of filteredClients">
              <td><strong>{{ c.company_name || '-' }}</strong></td>
              <td>{{ c.first_name }} {{ c.last_name }}</td>
              <td>{{ c.email }}</td>
              <td>{{ c.events_count || 0 }}</td>
              <td><a [routerLink]="['/employee/clients', c.id]" class="btn-view">Voir</a></td>
            </tr>
          </tbody>
        </table>
        <div *ngIf="!loading && clients.length === 0" class="empty">Aucun client trouvé</div>
      </div>
    </app-employee-layout>
  `,
  styleUrls: ['./employee-clients.scss']
})
export class EmployeeClientsComponent implements OnInit {
  clients: any[] = [];
  searchTerm = '';
  loading = true;

  constructor(private http: HttpClient) {}

  ngOnInit(): void { this.loadClients(); }

  loadClients(): void {
    this.loading = true;
    let url = 'http://localhost:8080/api/clients/read.php';
    if (this.searchTerm) url += `?search=${encodeURIComponent(this.searchTerm)}`;
    this.http.get<any>(url).subscribe({
      next: (r) => { this.clients = r.data || []; this.loading = false; },
      error: () => { this.loading = false; }
    });
  }

  get filteredClients() {
    if (!this.searchTerm) return this.clients;
    const s = this.searchTerm.toLowerCase();
    return this.clients.filter(c =>
      (c.company_name || '').toLowerCase().includes(s) ||
      (c.first_name || '').toLowerCase().includes(s) ||
      (c.last_name || '').toLowerCase().includes(s) ||
      (c.email || '').toLowerCase().includes(s)
    );
  }
}
