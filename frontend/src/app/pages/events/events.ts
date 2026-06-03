import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient, HttpParams } from '@angular/common/http';
import { HeaderComponent } from '../../components/header/header';
import { FooterComponent } from '../../components/footer/footer';

@Component({
  selector: 'app-events',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, HeaderComponent, FooterComponent],
  templateUrl: './events.html',
  styleUrl: './events.scss'
})
export class EventsComponent implements OnInit {
  events: any[] = [];
  filteredEvents: any[] = [];
  loading = true;
  error = '';

  filterType = '';
  filterTheme = '';
  filterDateStart = '';
  filterDateEnd = '';
  sortOrder: 'desc' | 'asc' = 'desc';

  defaultImage = 'assets/images/event-default.jpg';

  eventTypes = [
    { value: '', label: 'Tous les types' },
    { value: 'SÃ©minaire', label: 'SÃ©minaire' },
    { value: 'ConfÃ©rence', label: 'ConfÃ©rence' },
    { value: 'SoirÃ©e d\'entreprise', label: 'SoirÃ©e d\'entreprise' },
    { value: 'Team Building', label: 'Team Building' },
    { value: 'Autre', label: 'Autre' }
  ];

  themes = [
    { value: '', label: 'Tous les thÃ¨mes' },
    { value: 'Ã‰lÃ©gant', label: 'Ã‰lÃ©gant' },
    { value: 'Tropical', label: 'Tropical' },
    { value: 'RÃ©tro', label: 'RÃ©tro' },
    { value: 'High-Tech', label: 'High-Tech' },
    { value: 'Nature', label: 'Nature' },
    { value: 'Industriel', label: 'Industriel' }
  ];

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
    this.loadEvents();
  }

  loadEvents(): void {
    this.loading = true;

    let params = new HttpParams();

    if (this.filterType) {
      params = params.set('type', this.filterType);
    }

    if (this.filterTheme) {
      params = params.set('theme', this.filterTheme);
    }

    if (this.filterDateStart) {
      params = params.set('date_start', this.filterDateStart);
    }

    if (this.filterDateEnd) {
      params = params.set('date_end', this.filterDateEnd);
    }

    this.http.get<any>('/api/events/read_public.php', { params })
      .subscribe({
        next: (response) => {
          this.events = response.data || [];
          this.filteredEvents = this.sortEventsByDate(this.events);
          this.loading = false;
        },
        error: () => {
          this.error = 'Impossible de charger les Ã©vÃ©nements';
          this.loading = false;
        }
      });
  }

  applyFilters(): void {
    this.loadEvents();
  }

  applySorting(): void {
    this.filteredEvents = this.sortEventsByDate(this.events);
  }

  resetFilters(): void {
    this.filterType = '';
    this.filterTheme = '';
    this.filterDateStart = '';
    this.filterDateEnd = '';
    this.sortOrder = 'desc';
    this.loadEvents();
  }

  private sortEventsByDate(events: any[]): any[] {
    return [...events].sort((a, b) => {
      const dateA = new Date(a.start_date).getTime();
      const dateB = new Date(b.start_date).getTime();
      return this.sortOrder === 'asc' ? dateA - dateB : dateB - dateA;
    });
  }

  formatDate(dateString: string): string {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    });
  }

  getEventImage(imagePath: string | null): string {
    if (imagePath && imagePath.trim() !== '') {
      if (imagePath.startsWith('/uploads/')) {
        return '/api' + imagePath;
      }
      return imagePath;
    }
    return this.defaultImage;
  }

  onImageError(event: any): void {
    event.target.src = this.defaultImage;
  }
}
