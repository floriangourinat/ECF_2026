import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../_services/auth.service';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [FormsModule, CommonModule],
  templateUrl: './login.html', // Nom de fichier court correspondant à votre structure
  styleUrl: './login.scss'     // Nom de fichier court (.scss)
})
export class LoginComponent {

  // Variables liées aux champs du formulaire
  email: string = '';
  mot_de_passe: string = '';
  
  // Variable pour stocker et afficher les messages d'erreur
  errorMessage: string = '';

  constructor(
    private authService: AuthService, 
    private router: Router
  ) {}

  /**
   * Méthode déclenchée à la soumission du formulaire
   */
  onSubmit(): void {
    // Réinitialisation du message d'erreur
    this.errorMessage = '';

    // Appel au service d'authentification
    this.authService.login(this.email, this.mot_de_passe).subscribe({
      next: (data) => {
        // Redirection vers la page d'accueil (Dashboard) en cas de succès
        this.router.navigate(['/home']); 
      },
      error: (err) => {
        // Gestion des erreurs HTTP (ex: 401 Unauthorized)
        if (err.status === 401) {
          this.errorMessage = "Email ou mot de passe incorrect.";
        } else {
          this.errorMessage = "Erreur de connexion au serveur.";
        }
      }
    });
  }
}