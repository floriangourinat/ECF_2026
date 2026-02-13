import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonIcon, IonBadge, IonButton, IonTextarea, IonSpinner } from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { calendarOutline, locationOutline, personOutline, addOutline } from 'ionicons/icons';
import { environment } from '../../../environments/environment';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-event-detail',
  standalone: true,
  imports: [CommonModule, FormsModule, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonIcon, IonBadge, IonButton, IonTextarea, IonSpinner],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-back-button defaultHref="/tabs/events" text="Retour"></ion-back-button></ion-buttons>
        <ion-title>Détail événement</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <main aria-live="polite">
        <div *ngIf="loading" class="loading-center"><ion-spinner name="crescent" aria-label="Chargement"></ion-spinner></div>

        <div *ngIf="!loading && event">
          <ion-card>
            <ion-card-header>
              <ion-badge [color]="getStatusColor(event.status)" class="status-top">{{ statusLabels[event.status] }}</ion-badge>
              <ion-card-title>{{ event.name }}</ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <div class="info-row"><ion-icon name="calendar-outline" aria-hidden="true"></ion-icon><span>{{ formatDate(event.start_date) }} → {{ formatDate(event.end_date) }}</span></div>
              <div class="info-row" *ngIf="event.location"><ion-icon name="location-outline" aria-hidden="true"></ion-icon><span>{{ event.location }}</span></div>

              <button type="button" class="info-row client-link" (click)="openClient()" [attr.aria-label]="'Voir la fiche client de ' + (event.client_company || event.client_first_name + ' ' + event.client_last_name)">
                <ion-icon name="person-outline" aria-hidden="true"></ion-icon>
                <span>{{ event.client_company || event.client_first_name + ' ' + event.client_last_name }}</span>
                <small>→ Voir fiche client</small>
              </button>

              <p *ngIf="event.description" class="description">{{ event.description }}</p>
            </ion-card-content>
          </ion-card>

          <ion-card>
            <ion-card-header><ion-card-title>Notes ({{ notes.length }})</ion-card-title></ion-card-header>
            <ion-card-content>
              <div class="note-form">
                <ion-textarea
                  [(ngModel)]="newNote"
                  placeholder="Ajouter une note rapide..."
                  [rows]="3"
                  fill="outline"
                  label="Nouvelle note"
                  labelPlacement="stacked"
                ></ion-textarea>

                <ion-button expand="block" size="small" (click)="addNote()" [disabled]="addingNote || !newNote.trim()">
                  <ion-icon name="add-outline" slot="start" aria-hidden="true"></ion-icon>
                  {{ addingNote ? 'Ajout...' : 'Ajouter la note' }}
                </ion-button>
              </div>

              <div *ngIf="notes.length === 0" class="empty-notes">Aucune note</div>
              <div *ngFor="let note of notes" class="note-item">
                <div class="note-header"><strong>{{ note.first_name }} {{ note.last_name }}</strong><span class="note-date">{{ formatDateTime(note.created_at) }}</span></div>
                <p>{{ note.content }}</p>
              </div>
            </ion-card-content>
          </ion-card>
        </div>
      </main>
    </ion-content>
  `,
  styles: [`
    .loading-center { display:flex; justify-content:center; padding:60px; }
    .status-top { margin-bottom:8px; }
    .info-row { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid #f0f0f0; font-size:0.9rem; color:#555; width:100%; text-align:left; ion-icon { color:#f39c12; font-size:1.1rem; min-width:20px; } }
    .client-link { cursor:pointer; color:#1b6ec2; background:none; border:none; small { margin-left:auto; color:#555; font-size:0.75rem; } &:active { background:#f0f7ff; } }
    .description { margin-top:12px; color:#444; font-size:0.9rem; line-height:1.5; }
    .note-form { margin-bottom:16px; ion-textarea { margin-bottom:10px; } ion-button { --background:#f39c12; --border-radius:8px; } }
    .empty-notes { text-align:center; color:#666; padding:20px; }
    .note-item { background:#f8f9fa; border-radius:8px; padding:12px; margin-bottom:10px;
      .note-header { display:flex; justify-content:space-between; margin-bottom:6px; strong { font-size:0.85rem; color:#2c3e50; } .note-date { font-size:0.75rem; color:#666; } }
      p { margin:0; font-size:0.85rem; color:#444; line-height:1.4; }
    }
  `]
})
export class EventDetailPage implements OnInit {
  event: any = null; notes: any[] = []; loading = true; newNote = ''; addingNote = false;
  statusLabels: any = { 'draft':'Brouillon', 'client_review':'En attente', 'accepted':'Accepté', 'in_progress':'En cours', 'completed':'Terminé', 'cancelled':'Annulé' };

  constructor(private route: ActivatedRoute, private router: Router, private http: HttpClient, private auth: AuthService) {
    addIcons({ calendarOutline, locationOutline, personOutline, addOutline });
  }

  ngOnInit() {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) this.loadEvent(id);
  }

  loadEvent(id: string) {
    this.http.get<any>(`${environment.apiUrl}/events/read_detail.php?id=${id}`).subscribe({
      next: (r) => { this.event = r.data.event; this.notes = r.data.notes || []; this.loading = false; },
      error: () => { this.loading = false; }
    });
  }

  openClient() { if (this.event?.client_id) this.router.navigate(['/client', this.event.client_id]); }

  addNote() {
    if (!this.newNote.trim()) return;
    this.addingNote = true;
    this.http.post<any>(`${environment.apiUrl}/notes/create.php`, { event_id: this.event.id, author_id: this.auth.currentUserValue?.id, content: this.newNote }).subscribe({
      next: (r) => { if (r.success) { this.notes.unshift(r.data); this.newNote = ''; } this.addingNote = false; },
      error: () => { this.addingNote = false; }
    });
  }

  getStatusColor(s: string): string { return ({ draft:'medium', client_review:'warning', accepted:'primary', in_progress:'success', completed:'success', cancelled:'danger' } as any)[s] || 'medium'; }
  formatDate(d: string): string { return d ? new Date(d).toLocaleDateString('fr-FR', { day:'numeric', month:'long', year:'numeric' }) : '-'; }
  formatDateTime(d: string): string { return d ? new Date(d).toLocaleDateString('fr-FR', { day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' }) : '-'; }
}
