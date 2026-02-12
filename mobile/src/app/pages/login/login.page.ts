import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { IonContent, IonItem, IonInput, IonButton, IonSpinner, IonText } from '@ionic/angular/standalone';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule, IonContent, IonItem, IonInput, IonButton, IonSpinner, IonText],
  template: `
    <ion-content class="login-content">
      <div class="login-container">
        <div class="logo">
          <h1>📅 Innov'Events</h1>
          <p>Application Mobile</p>
        </div>
        <div class="form-card">
          <ion-item>
            <ion-input label="Email" labelPlacement="floating" type="email" [(ngModel)]="email" placeholder="votre&#64;email.com"></ion-input>
          </ion-item>
          <ion-item>
            <ion-input label="Mot de passe" labelPlacement="floating" type="password" [(ngModel)]="password"></ion-input>
          </ion-item>
          <ion-text color="danger" *ngIf="error">
            <p class="error-msg">{{ error }}</p>
          </ion-text>
          <ion-button expand="block" (click)="onLogin()" [disabled]="loading" class="login-btn">
            <ion-spinner *ngIf="loading" name="crescent" slot="start"></ion-spinner>
            {{ loading ? 'Connexion...' : 'Se connecter' }}
          </ion-button>
          <p class="access-note">Accès réservé aux administrateurs</p>
        </div>
      </div>
    </ion-content>
  `,
  styles: [`
    .login-content { --background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); }
    .login-container { display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:100%; padding:20px; }
    .logo { text-align:center; margin-bottom:40px; h1 { color:#f39c12; font-size:1.8rem; margin:0; } p { color:rgba(255,255,255,0.7); margin-top:8px; } }
    .form-card { width:100%; max-width:400px; background:white; border-radius:16px; padding:30px; box-shadow:0 10px 40px rgba(0,0,0,0.3); ion-item { --background:transparent; margin-bottom:10px; } }
    .login-btn { margin-top:20px; --background:#f39c12; --border-radius:10px; font-weight:600; }
    .error-msg { text-align:center; font-size:0.85rem; padding:8px; }
    .access-note { text-align:center; color:#999; font-size:0.8rem; margin-top:15px; }
  `]
})
export class LoginPage {
  email = ''; password = ''; loading = false; error = '';
  constructor(private authService: AuthService, private router: Router) {
    if (this.authService.isLoggedIn()) this.router.navigate(['/tabs']);
  }
  onLogin(): void {
    if (!this.email || !this.password) { this.error = 'Veuillez remplir tous les champs'; return; }
    this.loading = true; this.error = '';
    this.authService.login(this.email, this.password).subscribe({
      next: () => { this.router.navigate(['/tabs']); },
      error: (err) => { this.error = err.error?.message || err.message || 'Identifiants incorrects'; this.loading = false; }
    });
  }
}
