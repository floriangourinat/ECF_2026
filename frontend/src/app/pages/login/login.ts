import { Component } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
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
  infoMessage: string = '';
  loading: boolean = false;

  showResendVerification: boolean = false;
  resendLoading: boolean = false;
  resendMessage: string = '';

  private returnUrl: string = '/dashboard';

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
    private router: Router,
    private route: ActivatedRoute
  ) {
    // Pré-remplissage email si on arrive depuis register
    const emailParam = this.route.snapshot.queryParamMap.get('email');
    if (emailParam) {
      this.email = emailParam;
      this.infoMessage = "Si vous venez de créer votre compte, pensez à confirmer votre email avant de vous connecter.";
    }

    // ✅ ReturnUrl : si l'utilisateur a tenté d'accéder à une page protégée
    const ru = this.route.snapshot.queryParamMap.get('returnUrl');
    if (ru && typeof ru === 'string' && ru.trim().length > 0) {
      this.returnUrl = ru;
    }
  }

  onSubmit(): void {
    if (!this.email || !this.mot_de_passe) {
      this.errorMessage = "Veuillez remplir tous les champs.";
      return;
    }

    this.errorMessage = '';
    this.resendMessage = '';
    this.showResendVerification = false;
    this.loading = true;

    this.authService.login(this.email, this.mot_de_passe).subscribe({
      next: (user) => {
        this.loading = false;

        // ✅ Si le backend force le changement de mot de passe (cas mot de passe oublié)
        if (user?.must_change_password) {
          const role = user.role;
          const target =
            role === 'admin' ? '/admin/change-password'
            : role === 'employee' ? '/employee/change-password'
            : '/client/profile';

          // On conserve le returnUrl pour revenir ensuite (si tu veux l’exploiter dans ces pages)
          this.router.navigate([target], { queryParams: { returnUrl: this.returnUrl } });
          return;
        }

        // ✅ Conformité énoncé : redirection vers l’action initiale
        this.router.navigateByUrl(this.returnUrl || '/dashboard');
      },
      error: (err) => {
        this.loading = false;

        const apiMessage = err?.error?.message;
        if (err.status === 401) {
          this.errorMessage = apiMessage || "Email ou mot de passe incorrect.";
          return;
        }

        if (err.status === 403) {
          // 2 cas : suspendu ou email non vérifié
          this.errorMessage = apiMessage || "Accès refusé.";
          if ((apiMessage || '').toLowerCase().includes('email non vérifié')) {
            this.showResendVerification = true;
          }
          return;
        }

        this.errorMessage = "Erreur de connexion au serveur.";
      }
    });
  }

  resendVerification(): void {
    if (!this.email) return;

    this.resendLoading = true;
    this.resendMessage = '';

    this.authService.resendVerification(this.email).subscribe({
      next: (res) => {
        this.resendLoading = false;
        this.resendMessage = res?.message || "Email de vérification renvoyé (si un compte existe).";
      },
      error: () => {
        this.resendLoading = false;
        this.resendMessage = "Erreur lors du renvoi de l'email.";
      }
    });
  }
}
