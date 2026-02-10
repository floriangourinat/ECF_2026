// frontend/src/app/pages/login/login.ts
import { Component } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../_services/auth.service';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { HeaderComponent } from '../../components/header/header';
import { FooterComponent } from '../../components/footer/footer';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [FormsModule, CommonModule, RouterLink, HeaderComponent, FooterComponent],
  templateUrl: './login.html',
  styleUrl: './login.scss'
})
export class LoginComponent {

  email: string = '';
  mot_de_passe: string = '';
  errorMessage: string = '';
  loading: boolean = false;

  // Exposer loginForm pour les tests
  loginForm = {
    contains: (field: string) => ['email', 'password'].includes(field),
    get: (field: string) => {
      if (field === 'email') return { value: this.email, valid: this.email !== '' && this.email.includes('@'), setValue: (v: string) => this.email = v };
      if (field === 'password') return { value: this.mot_de_passe, valid: this.mot_de_passe !== '', setValue: (v: string) => this.mot_de_passe = v };
      return null;
    },
    valid: false
  };

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  onSubmit(): void {
    if (!this.email || !this.mot_de_passe) {
      this.errorMessage = "Veuillez remplir tous les champs.";
      return;
    }

    this.errorMessage = '';
    this.loading = true;

    this.authService.login(this.email, this.mot_de_passe).subscribe({
      next: () => {
        this.loading = false;
        this.router.navigate(['/dashboard']);
      },
      error: (err) => {
        this.loading = false;
        if (err.status === 401) {
          this.errorMessage = "Email ou mot de passe incorrect.";
        } else if (err.status === 403) {
          this.errorMessage = "Compte suspendu. Contactez l'administrateur.";
        } else {
          this.errorMessage = "Erreur de connexion au serveur.";
        }
      }
    });
  }
}
