import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonBadge, IonRefresher, IonRefresherContent, IonSpinner, IonIcon } from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { locationOutline, calendarOutline } from 'ionicons/icons';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-events',
  standalone: true,
  imports: [CommonModule, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItem, IonLabel, IonBadge, IonRefresher, IonRefresherContent, IonSpinner, IonIcon],
  template: `
    <ion-header><ion-toolbar><ion-title>Événements à venir</ion-title></ion-toolbar></ion-header>
    <ion-content>
      <main aria-live="polite">
        <ion-refresher slot="fixed" (ionRefresh)="doRefresh($event)"><ion-refresher-content></ion-refresher-content></ion-refresher>
        <div *ngIf="loading" class="loading-center"><ion-spinner name="crescent" color="primary" aria-label="Chargement"></ion-spinner></div>
        <div *ngIf="!loading && events.length === 0" class="empty-state"><p>Aucun événement à venir</p></div>
        <ion-list *ngIf="!loading && events.length > 0" lines="none" class="event-list" aria-label="Liste des événements">
          <ion-item *ngFor="let event of events" (click)="openEvent(event)" button detail class="event-card" [attr.aria-label]="'Ouvrir le détail de ' + event.name">
            <ion-label>
              <h2>{{ event.name }}</h2>
              <p><ion-icon name="calendar-outline" aria-hidden="true"></ion-icon> {{ formatDate(event.start_date) }}</p>
              <p *ngIf="event.location"><ion-icon name="location-outline" aria-hidden="true"></ion-icon> {{ event.location }}</p>
              <p class="client-name">{{ event.client_company || event.client_first_name + ' ' + event.client_last_name }}</p>
            </ion-label>
            <ion-badge slot="end" [color]="getStatusColor(event.status)">{{ statusLabels[event.status] }}</ion-badge>
          </ion-item>
        </ion-list>
      </main>
    </ion-content>
  `,
  styles: [`
    .loading-center { display:flex; justify-content:center; padding:60px; }
    .empty-state { text-align:center; padding:60px 20px; color:#666; font-size:1.1rem; }
    .event-list { padding:8px; }
    .event-card { --background:white; --border-radius:12px; --padding-start:16px; margin-bottom:10px; box-shadow:0 2px 8px rgba(0,0,0,0.08);
      h2 { font-weight:600; font-size:1rem; color:#2c3e50; margin-bottom:6px; }
      p { display:flex; align-items:center; gap:6px; font-size:0.9rem; color:#4a5660; margin:3px 0; }
      .client-name { color:#1d6fa2; font-weight:500; margin-top:6px; }
      ion-icon { font-size:0.9rem; }
    }
  `]
})
export class EventsPage implements OnInit {
  events: any[] = []; loading = true;
  statusLabels: any = { 'draft':'Brouillon', 'client_review':'En attente', 'accepted':'Accepté', 'in_progress':'En cours', 'completed':'Terminé', 'cancelled':'Annulé' };
  constructor(private http: HttpClient, private router: Router) { addIcons({ locationOutline, calendarOutline }); }
  ngOnInit() { this.loadEvents(); }
  loadEvents() {
    this.loading = true;
    this.http.get<any>(`${environment.apiUrl}/events/read_all.php`).subscribe({
      next: (r) => {
        this.events = (r.data || []).filter((e: any) => e.status !== 'cancelled' && e.status !== 'completed')
          .sort((a: any, b: any) => new Date(a.start_date).getTime() - new Date(b.start_date).getTime());
        this.loading = false;
      },
      error: () => { this.loading = false; }
    });
  }
  openEvent(event: any) { this.router.navigate(['/event', event.id]); }
  doRefresh(event: any) { this.loadEvents(); setTimeout(() => event.target.complete(), 1000); }
  getStatusColor(s: string): string { return ({ draft:'medium', client_review:'warning', accepted:'primary', in_progress:'success', completed:'success', cancelled:'danger' } as any)[s] || 'medium'; }
  formatDate(d: string): string { return d ? new Date(d).toLocaleDateString('fr-FR', { day:'numeric', month:'long', year:'numeric' }) : '-'; }
}
