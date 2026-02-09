import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { ClientLayoutComponent } from '../../../components/client-layout/client-layout';
import { AuthService } from '../../../_services/auth.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-client-profile',
  standalone: true,
  imports: [CommonModule, FormsModule, ClientLayoutComponent],
  templateUrl: './client-profile.html',
  styleUrls: ['./client-profile.scss']
})
export class ClientProfileComponent implements OnInit {
  loading = true;
  success = '';
  error = '';
  passwordSuccess = '';
  passwordError = '';
  profile: any = {
    first_name: '',
    last_name: '',
    email: '',
    company_name: '',
    phone: '',
    address: ''
  };
  currentPassword = '';
  newPassword = '';
  confirmPassword = '';

  private passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

  constructor(private http: HttpClient, private authService: AuthService, private router: Router) {}

  ngOnInit(): void {
    const userId = this.authService.currentUserValue?.id;
    if (!userId) return;

    this.http.get<any>(`http://localhost:8080/api/clients/read_by_user.php?user_id=${userId}`).subscribe({
      next: (response) => {
        this.profile = response.data || this.profile;
        this.loading = false;
      },
      error: () => {
        this.error = 'Impossible de charger le profil';
        this.loading = false;
      }
    });
  }

  saveProfile(): void {
    const userId = this.authService.currentUserValue?.id;
    if (!userId) return;
    this.success = '';
    this.error = '';

    this.http.put<any>('http://localhost:8080/api/clients/update_profile.php', {
      user_id: userId,
      first_name: this.profile.first_name,
      last_name: this.profile.last_name,
      email: this.profile.email,
      company_name: this.profile.company_name,
      phone: this.profile.phone,
      address: this.profile.address
    }).subscribe({
      next: (response) => {
        this.success = response.message || 'Profil mis à jour';
      },
      error: (err) => {
        this.error = err?.error?.message || 'Erreur lors de la mise à jour';
      }
    });
  }

  changePassword(): void {
    const userId = this.authService.currentUserValue?.id;
    if (!userId) return;

    this.passwordSuccess = '';
    this.passwordError = '';

    if (this.newPassword !== this.confirmPassword) {
      this.passwordError = 'Les mots de passe ne correspondent pas.';
      return;
    }

    if (!this.passwordPattern.test(this.newPassword)) {
      this.passwordError = 'Le mot de passe ne respecte pas les règles.';
      return;
    }

    this.http.post<any>('http://localhost:8080/api/auth/change-password.php', {
      user_id: userId,
      current_password: this.currentPassword,
      new_password: this.newPassword
    }).subscribe({
      next: (response) => {
        this.passwordSuccess = response.message || 'Mot de passe modifié.';
        this.currentPassword = '';
        this.newPassword = '';
        this.confirmPassword = '';
      },
      error: (err) => {
        this.passwordError = err?.error?.message || 'Erreur lors de la modification.';
      }
    });
  }

  deleteAccount(): void {
    const userId = this.authService.currentUserValue?.id;
    if (!userId) return;
    if (!confirm('Confirmez-vous la suppression totale de votre compte ?')) return;

    this.http.delete<any>('http://localhost:8080/api/clients/delete_self.php', {
      body: { user_id: userId }
    }).subscribe({
      next: () => {
        this.authService.logout();
        this.router.navigate(['/home']);
      },
      error: (err) => {
        alert(err?.error?.message || 'Erreur lors de la suppression');
      }
    });
  }
}
