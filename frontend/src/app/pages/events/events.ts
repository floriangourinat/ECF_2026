import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { HeaderComponent } from '../../components/header/header';
import { FooterComponent } from '../../components/footer/footer';

interface Event {
  id: number;
  name: string;
  description: string;
  event_type: string;
  theme: string;
  location: string;
  start_date: string;
  end_date: string;
  image_url: string;
  client_company: string;
}

@Component({
  selector: 'app-events',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, HeaderComponent, FooterComponent],
  templateUrl: './events.html',
  styleUrl: './events.scss'
})
export class EventsComponent implements OnInit {
  events: Event[] = [];
  filteredEvents: Event[] = [];
  loading = true;
  error = '';

  // Filtres
  filterType = '';
  filterTheme = '';
  filterDateStart = '';
  filterDateEnd = '';

  eventTypes = [
    { value: '', label: 'Tous les types' },
    { value: 'seminaire', label: 'Séminaire' },
    { value: 'conference', label: 'Conférence' },
    { value: 'soiree', label: 'Soirée d\'entreprise' },
    { value: 'team_building', label: 'Team Building' },
    { value: 'autre', label: 'Autre' }
  ];

  themes = [
    { value: '', label: 'Tous les thèmes' },
    { value: 'corporate', label: 'Corporate' },
    { value: 'luxe', label: 'Luxe' },
    { value: 'decontracte', label: 'Décontracté' },
    { value: 'nature', label: 'Nature' },
    { value: 'tech', label: 'Tech' }
  ];

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
    this.loadEvents();
  }

  loadEvents(): void {
    this.loading = true;
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

      if (this.filterDateStart && event.start_date < this.filterDateStart) {
        match = false;
      }

      if (this.filterDateEnd && event.end_date > this.filterDateEnd) {
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
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    });
  }

  getEventTypeLabel(type: string): string {
    const found = this.eventTypes.find(t => t.value === type);
    return found ? found.label : type;
  }
}