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
    'CONNEXION_REUSSIE': 'Connexion réussie',
    'CONNEXION_ECHOUEE': 'Connexion échouée',
    'CREATION_CLIENT': 'Création client',
    'MODIFICATION_CLIENT': 'Modification client',
    'SUPPRESSION_CLIENT': 'Suppression client',
    'CREATION_EVENEMENT': 'Création événement',
    'MODIFICATION_STATUT_EVENEMENT': 'Modification statut événement',
    'GENERATION_DEVIS_PDF': 'Génération PDF devis'
  };

  entityLabels: { [key: string]: string } = {
    'user': 'Utilisateur',
    'client': 'Client',
    'event': 'Événement',
    'quote': 'Devis'
  };

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
    this.loadStats();
    this.loadLogs();
  }

  loadStats(): void {
    this.http.get<any>('/api/logs/stats.php').subscribe({
      next: (response) => {
        this.stats = response.data;
        this.users = response.data.users || {};
      }
    });
  }

  loadLogs(): void {
    this.loading = true;
    let url = '/api/logs/read.php?';

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
      'CREATION_CLIENT': 'action-create',
      'MODIFICATION_CLIENT': 'action-update',
      'SUPPRESSION_CLIENT': 'action-delete',
      'CONNEXION_REUSSIE': 'action-login',
      'CONNEXION_ECHOUEE': 'action-delete',
      'CREATION_EVENEMENT': 'action-create',
      'MODIFICATION_STATUT_EVENEMENT': 'action-update',
      'GENERATION_DEVIS_PDF': 'action-create'
    };
    return classes[action] || '';
  }

  getActionIcon(action: string): string {
    const icons: { [key: string]: string } = {
      'CONNEXION_REUSSIE': '🔑',
      'CONNEXION_ECHOUEE': '🚫',
      'CREATION_CLIENT': '➕',
      'MODIFICATION_CLIENT': '✏️',
      'SUPPRESSION_CLIENT': '🗑️',
      'CREATION_EVENEMENT': '🎉',
      'MODIFICATION_STATUT_EVENEMENT': '🔄',
      'GENERATION_DEVIS_PDF': '📄'
    };
    return icons[action] || '📝';
  }

  private countActions(predicate: (actionKey: string) => boolean): number {
    if (!this.stats?.by_action) return 0;

    return Object.entries(this.stats.by_action).reduce((total, [actionKey, count]) => {
      if (predicate(actionKey)) {
        return total + Number(count || 0);
      }
      return total;
    }, 0);
  }

  getCreateCount(): number {
    return this.countActions((actionKey) => {
      const key = actionKey.toUpperCase();
      return key.includes('CREATION') || key === 'CREATE';
    });
  }

  getUpdateCount(): number {
    return this.countActions((actionKey) => {
      const key = actionKey.toUpperCase();
      return key.includes('MODIFICATION') || key === 'UPDATE';
    });
  }

  getDeleteCount(): number {
    return this.countActions((actionKey) => {
      const key = actionKey.toUpperCase();
      return key.includes('SUPPRESSION') || key === 'DELETE';
    });
  }

  getLoginCount(): number {
    return this.countActions((actionKey) => {
      const key = actionKey.toUpperCase();
      return key === 'CONNEXION_REUSSIE' || key === 'LOGIN';
    });
  }

  getDetailsKeys(details: any): string[] {
    return details ? Object.keys(details) : [];
  }
}
