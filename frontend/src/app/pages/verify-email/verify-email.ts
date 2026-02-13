import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { AuthService } from '../../_services/auth.service';
import { HeaderComponent } from '../../components/header/header';
import { FooterComponent } from '../../components/footer/footer';

@Component({
  selector: 'app-verify-email',
  standalone: true,
  imports: [CommonModule, RouterLink, HeaderComponent, FooterComponent],
  templateUrl: './verify-email.html',
  styleUrl: './verify-email.scss'
})
export class VerifyEmailComponent {
  loading = true;
  message = '';
  isSuccess = false;

  constructor(private route: ActivatedRoute, private authService: AuthService) {
    const token = this.route.snapshot.queryParamMap.get('token');

    if (!token) {
      this.loading = false;
      this.isSuccess = false;
      this.message = "Token manquant. Vérifiez le lien reçu par email.";
      return;
    }

    this.authService.verifyEmail(token).subscribe({
      next: (res) => {
        this.loading = false;
        this.isSuccess = true;
        this.message = res?.message || "Email vérifié. Vous pouvez vous connecter.";
      },
      error: (err) => {
        this.loading = false;
        this.isSuccess = false;
        this.message = err?.error?.message || "Lien invalide ou expiré.";
      }
    });
  }
}
