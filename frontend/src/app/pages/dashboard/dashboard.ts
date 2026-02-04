import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { AuthService } from '../../_services/auth.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule],
  template: `<p>Redirection...</p>`
})
export class DashboardComponent implements OnInit {
  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    const user = this.authService.currentUserValue;
    
    if (user) {
      switch (user.role) {
        case 'admin':
          this.router.navigate(['/admin/dashboard']);
          break;
        case 'employee':
          this.router.navigate(['/employee/dashboard']);
          break;
        case 'client':
          this.router.navigate(['/client/dashboard']);
          break;
        default:
          this.router.navigate(['/home']);
      }
    } else {
      this.router.navigate(['/login']);
    }
  }
}