import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';

interface Log {
  id: string;
  action: string;
  entity: string;
  entity_id: number;
  user_id: number;
  details: any;
  ip_address: string;
  created_at: string;
}

interface Stats {
  total: number;
  by_action: { [key: string]: number };
  by_entity: { [key: string]: number };
  users: { [key: string]: string };
}

@Component({
  selector: 'app-logs-list',
  standalone: true,
  imports: [CommonModule, FormsModule, AdminLayoutComponent],
  templateUrl: './logs-list.html',
  styleUrls: ['./logs-list.scss']
})
export class LogsListComponent implements OnInit {
  logs: Log[] = [];
  stats: Stats | null = null;
  users: { [key: string]: string } = {};
  loading = true;
  error = '';

  filterAction = '';
  filterEntity = '';
  filterDateFrom = '';
  filterDateTo = '';

  actionLabels: { [key: string]: string } = {
    'create': 'Création',
    'update': 'Modification',
    'delete': 'Suppression',
    'login': 'Connexion',
    'logout': 'Déconnexion',
    'status_change': 'Changement statut'
  };

  entityLabels: { [key: string]: string } = {
    'user': 'Utilisateur',
    'client': 'Client',
    'event': 'Événement',
    'quote': 'Devis',
    'prospect': 'Prospect',
    'review': 'Avis',
    'employee': 'Employé'
  };

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
    this.loadStats();
    this.loadLogs();
  }

  loadStats(): void {
    this.http.get<any>('http://localhost:8080/api/logs/stats.php').subscribe({
      next: (response) => {
        this.stats = response.data;
        this.users = response.data.users || {};
      }
    });
  }

  loadLogs(): void {
    this.loading = true;
    let url = 'http://localhost:8080/api/logs/read.php?';

    if (this.filterAction) url += `action=${this.filterAction}&`;
    if (this.filterEntity) url += `entity=${this.filterEntity}&`;
    if (this.filterDateFrom) url += `date_from=${this.filterDateFrom}&`;
    if (this.filterDateTo) url += `date_to=${this.filterDateTo}&`;

    this.http.get<any>(url).subscribe({
      next: (response) => {
        this.logs = response.data || [];
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger les logs';
        this.loading = false;
      }
    });
  }

  onFilterChange(): void {
    this.loadLogs();
  }

  resetFilters(): void {
    this.filterAction = '';
    this.filterEntity = '';
    this.filterDateFrom = '';
    this.filterDateTo = '';
    this.loadLogs();
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleString('fr-FR');
  }

  getUserName(userId: number): string {
    return this.users[userId] || `Utilisateur #${userId}`;
  }

  getActionClass(action: string): string {
    const classes: { [key: string]: string } = {
      'create': 'action-create',
      'update': 'action-update',
      'delete': 'action-delete',
      'login': 'action-login',
      'logout': 'action-logout'
    };
    return classes[action] || '';
  }

  getActionIcon(action: string): string {
    const icons: { [key: string]: string } = {
      'create': '➕',
      'update': '✏️',
      'delete': '🗑️',
      'login': '🔑',
      'logout': '🚪',
      'status_change': '🔄'
    };
    return icons[action] || '📝';
  }

  getDetailsKeys(details: any): string[] {
    return details ? Object.keys(details) : [];
  }
}