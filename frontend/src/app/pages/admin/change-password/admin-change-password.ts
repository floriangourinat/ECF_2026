import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';
import { AuthService } from '../../../_services/auth.service';

@Component({
  selector: 'app-admin-change-password',
  standalone: true,
  imports: [CommonModule, FormsModule, AdminLayoutComponent],
  templateUrl: './admin-change-password.html',
  styleUrls: ['./admin-change-password.scss']
})
export class AdminChangePasswordComponent {
  currentPassword = '';
  newPassword = '';
  confirmPassword = '';
  loading = false;
  successMessage = '';
  errorMessage = '';

  private passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

  constructor(private http: HttpClient, private auth: AuthService) {}

  submit(): void {
    this.successMessage = '';
    this.errorMessage = '';

    if (!this.auth.currentUserValue?.id) {
      this.errorMessage = 'Utilisateur non connecté.';
      return;
    }

    if (this.newPassword !== this.confirmPassword) {
      this.errorMessage = 'Les mots de passe ne correspondent pas.';
      return;
    }

    if (!this.passwordPattern.test(this.newPassword)) {
      this.errorMessage = 'Le mot de passe ne respecte pas les règles.';
      return;
    }

    this.loading = true;

    this.http.post<any>('http://localhost:8080/api/auth/change-password.php', {
      user_id: this.auth.currentUserValue.id,
      current_password: this.currentPassword,
      new_password: this.newPassword
    }).subscribe({
      next: (response) => {
        this.successMessage = response.message || 'Mot de passe modifié.';
        this.currentPassword = '';
        this.newPassword = '';
        this.confirmPassword = '';
        this.loading = false;
      },
      error: (err) => {
        this.errorMessage = err?.error?.message || 'Erreur lors de la modification.';
        this.loading = false;
      }
    });
  }
}
