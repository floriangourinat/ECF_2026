import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../_services/auth.service';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [FormsModule, CommonModule],
  templateUrl: './login.html', // Nom de fichier court
  styleUrl: './login.scss'     // Nom de fichier court (.scss)
})
export class LoginComponent {

  email: string = '';
  mot_de_passe: string = '';
  errorMessage: string = '';

  constructor(
    private authService: AuthService, 
    private router: Router
  ) {}

  onSubmit(): void {
    this.errorMessage = '';

    this.authService.login(this.email, this.mot_de_passe).subscribe({
      next: (data) => {
        this.router.navigate(['/']); 
      },
      error: (err) => {
        if (err.status === 401) {
          this.errorMessage = "Email ou mot de passe incorrect.";
        } else {
          this.errorMessage = "Erreur de connexion au serveur.";
        }
      }
    });
  }
}