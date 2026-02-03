import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
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
  participant_count: number;
  client_company: string;
}

@Component({
  selector: 'app-event-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, HeaderComponent, FooterComponent],
  templateUrl: './event-detail.html',
  styleUrl: './event-detail.scss'
})
export class EventDetailComponent implements OnInit {
  event: Event | null = null;
  loading = true;
  error = '';

  eventTypeLabels: { [key: string]: string } = {
    'seminaire': 'Séminaire',
    'conference': 'Conférence',
    'soiree': 'Soirée d\'entreprise',
    'team_building': 'Team Building',
    'autre': 'Autre'
  };

  constructor(
    private route: ActivatedRoute,
    private http: HttpClient
  ) {}

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.loadEvent(id);
    }
  }

  loadEvent(id: string): void {
    this.http.get<any>(`http://localhost:8080/api/events/read_one.php?id=${id}`)
      .subscribe({
        next: (response) => {
          this.event = response.data;
          this.loading = false;
        },
        error: () => {
          this.error = 'Événement non trouvé';
          this.loading = false;
        }
      });
  }

  formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('fr-FR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    });
  }

  formatTime(dateString: string): string {
    return new Date(dateString).toLocaleTimeString('fr-FR', {
      hour: '2-digit',
      minute: '2-digit'
    });
  }
}