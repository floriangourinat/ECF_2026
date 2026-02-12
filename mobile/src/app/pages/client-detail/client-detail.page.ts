import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonIcon, IonSpinner } from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { callOutline, mailOutline, navigateOutline, businessOutline, personOutline } from 'ionicons/icons';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-client-detail',
  standalone: true,
  imports: [CommonModule, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonIcon, IonSpinner],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-back-button text="Retour"></ion-back-button></ion-buttons>
        <ion-title>Fiche client</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      <div *ngIf="loading" class="loading-center"><ion-spinner name="crescent"></ion-spinner></div>
      <div *ngIf="!loading && client">
        <ion-card>
          <ion-card-header><ion-card-title><ion-icon name="person-outline"></ion-icon> {{ client.first_name }} {{ client.last_name }}</ion-card-title></ion-card-header>
          <ion-card-content>
            <div *ngIf="client.company_name" class="info-row"><ion-icon name="business-outline"></ion-icon><span>{{ client.company_name }}</span></div>
          </ion-card-content>
        </ion-card>
        <ion-card class="actions-card">
          <ion-card-header><ion-card-title>Actions rapides</ion-card-title></ion-card-header>
          <ion-card-content>
            <a *ngIf="client.phone" [href]="'tel:' + client.phone" class="action-btn action-call">
              <ion-icon name="call-outline"></ion-icon><div><strong>Appeler</strong><span>{{ client.phone }}</span></div>
            </a>
            <a *ngIf="client.email" [href]="'mailto:' + client.email" class="action-btn action-email">
              <ion-icon name="mail-outline"></ion-icon><div><strong>Envoyer un email</strong><span>{{ client.email }}</span></div>
            </a>
            <a *ngIf="client.address" [href]="getMapsUrl()" target="_blank" class="action-btn action-map">
              <ion-icon name="navigate-outline"></ion-icon><div><strong>Itinéraire</strong><span>{{ client.address }}</span></div>
            </a>
            <div *ngIf="!client.phone && !client.email && !client.address" class="no-info">Aucune information de contact disponible</div>
          </ion-card-content>
        </ion-card>
        <ion-card *ngIf="events.length > 0">
          <ion-card-header><ion-card-title>🎉 Événements ({{ events.length }})</ion-card-title></ion-card-header>
          <ion-card-content>
            <div *ngFor="let evt of events" class="event-mini"><strong>{{ evt.name }}</strong><span>{{ formatDate(evt.start_date) }} · {{ evt.location || '-' }}</span></div>
          </ion-card-content>
        </ion-card>
      </div>
    </ion-content>
  `,
  styles: [`
    .loading-center { display:flex; justify-content:center; padding:60px; }
    .info-row { display:flex; align-items:center; gap:10px; color:#555; ion-icon { color:#f39c12; } }
    .actions-card ion-card-content { padding:8px 16px; }
    .action-btn { display:flex; align-items:center; gap:14px; padding:16px; border-radius:12px; margin-bottom:10px; text-decoration:none; cursor:pointer; transition:transform 0.1s; &:active { transform:scale(0.98); }
      ion-icon { font-size:1.6rem; min-width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; }
      div { display:flex; flex-direction:column; strong { font-size:0.95rem; } span { font-size:0.8rem; color:#777; margin-top:2px; } }
    }
    .action-call { background:#e8f8f0; ion-icon { background:#27ae60; } strong { color:#27ae60; } }
    .action-email { background:#eaf2fe; ion-icon { background:#3498db; } strong { color:#3498db; } }
    .action-map { background:#fef5e7; ion-icon { background:#f39c12; } strong { color:#e67e22; } }
    .no-info { text-align:center; color:#999; padding:20px; }
    .event-mini { padding:10px 0; border-bottom:1px solid #f0f0f0; strong { display:block; font-size:0.9rem; color:#2c3e50; } span { font-size:0.8rem; color:#999; } &:last-child { border-bottom:none; } }
  `]
})
export class ClientDetailPage implements OnInit {
  client: any = null; events: any[] = []; loading = true;
  constructor(private route: ActivatedRoute, private http: HttpClient) { addIcons({ callOutline, mailOutline, navigateOutline, businessOutline, personOutline }); }
  ngOnInit() { const id = this.route.snapshot.paramMap.get('id'); if (id) this.loadClient(id); }
  loadClient(id: string) {
    this.http.get<any>(`${environment.apiUrl}/clients/read_one.php?id=${id}`).subscribe({
      next: (r) => { this.client = r.data?.client || r.data; this.events = r.data?.events || []; this.loading = false; },
      error: () => { this.loading = false; }
    });
  }
  getMapsUrl(): string { return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(this.client.address || '')}`; }
  formatDate(d: string): string { return d ? new Date(d).toLocaleDateString('fr-FR', { day:'numeric', month:'short', year:'numeric' }) : '-'; }
}
