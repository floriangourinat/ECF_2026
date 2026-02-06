import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
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

  defaultImage = 'assets/images/event-default.jpg';

  eventTypes = [
    { value: '', label: 'Tous les types' },
    { value: 'Séminaire', label: 'Séminaire' },
    { value: 'Conférence', label: 'Conférence' },
    { value: 'Soirée d\'entreprise', label: 'Soirée d\'entreprise' },
    { value: 'Team Building', label: 'Team Building' },
    { value: 'Autre', label: 'Autre' }
  ];

  themes = [
    { value: '', label: 'Tous les thèmes' },
    { value: 'Élégant', label: 'Élégant' },
    { value: 'Tropical', label: 'Tropical' },
    { value: 'Rétro', label: 'Rétro' },
    { value: 'High-Tech', label: 'High-Tech' },
    { value: 'Nature', label: 'Nature' },
    { value: 'Industriel', label: 'Industriel' }
  ];

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
    this.loadEvents();
  }

  loadEvents(): void {
    this.http.get<any>('http://localhost:8080/api/events/read_public.php')
      .subscribe({
        next: (response) => {
          this.events = response.data || [];
          this.filteredEvents = this.events;
          this.loading = false;
        },
        error: () => {
          this.error = 'Impossible de charger les événements';
          this.loading = false;
        }
      });
  }

  applyFilters(): void {
    this.filteredEvents = this.events.filter(event => {
      let match = true;

      if (this.filterType && event.event_type !== this.filterType) {
        match = false;
      }

      if (this.filterTheme && event.theme !== this.filterTheme) {
        match = false;
      }

      if (this.filterDateStart && new Date(event.start_date) < new Date(this.filterDateStart)) {
        match = false;
      }

      if (this.filterDateEnd && new Date(event.end_date) > new Date(this.filterDateEnd)) {
        match = false;
      }

      return match;
    });
  }

  resetFilters(): void {
    this.filterType = '';
    this.filterTheme = '';
    this.filterDateStart = '';
    this.filterDateEnd = '';
    this.filteredEvents = this.events;
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
      // Si c'est un chemin relatif du backend
      if (imagePath.startsWith('/uploads/')) {
        return 'http://localhost:8080' + imagePath;
      }
      return imagePath;
    }
    return this.defaultImage;
  }

  onImageError(event: any): void {
    event.target.src = this.defaultImage;
  }
}