import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
// L'import de User va marcher maintenant qu'on a mis "export" dans le service
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

  currentUser: User | null = null;

  constructor(
    private authService: AuthService,
    private router: Router
  ) {
    // CORRECTION ICI : On retire les parenthèses () et on utilise le bon nom
    this.currentUser = this.authService.currentUserValue;
  }

  logout(): void {
    this.authService.logout();
    this.router.navigate(['/login']);
  }
}