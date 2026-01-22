import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AuthService, User } from '../../_services/auth.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.scss'
})
export class DashboardComponent {

  // Variable pour stocker les infos de l'utilisateur connecté
  currentUser: User | null = null;

  constructor(
    private authService: AuthService, 
    private router: Router
  ) {
    // Récupération de l'utilisateur au chargement de la page
    this.currentUser = this.authService.getUser();
  }

  /**
   * Méthode de déconnexion
   */
  logout(): void {
    this.authService.logout();
    this.router.navigate(['/login']);
  }
}