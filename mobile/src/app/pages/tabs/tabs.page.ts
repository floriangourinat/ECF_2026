import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { IonTabs, IonTabBar, IonTabButton, IonIcon, IonLabel } from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { calendarOutline, documentTextOutline, cartOutline, logOutOutline } from 'ionicons/icons';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-tabs',
  standalone: true,
  imports: [IonTabs, IonTabBar, IonTabButton, IonIcon, IonLabel],
  template: `
    <ion-tabs>
      <ion-tab-bar slot="bottom">
        <ion-tab-button tab="events"><ion-icon name="calendar-outline"></ion-icon><ion-label>Événements</ion-label></ion-tab-button>
        <ion-tab-button tab="cgu"><ion-icon name="document-text-outline"></ion-icon><ion-label>CGU</ion-label></ion-tab-button>
        <ion-tab-button tab="cgv"><ion-icon name="cart-outline"></ion-icon><ion-label>CGV</ion-label></ion-tab-button>
        <ion-tab-button (click)="logout()"><ion-icon name="log-out-outline"></ion-icon><ion-label>Déconnexion</ion-label></ion-tab-button>
      </ion-tab-bar>
    </ion-tabs>
  `,
  styles: [`ion-tab-bar { --background:#2c3e50; --color:rgba(255,255,255,0.6); --color-selected:#f39c12; }`]
})
export class TabsPage {
  constructor(private auth: AuthService, private router: Router) { addIcons({ calendarOutline, documentTextOutline, cartOutline, logOutOutline }); }
  logout() { this.auth.logout(); this.router.navigate(['/login']); }
}
