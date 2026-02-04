import { inject } from '@angular/core';
import { Router, CanActivateFn } from '@angular/router';
import { AuthService } from '../_services/auth.service';

// Guard pour les pages protégées (nécessite connexion)
export const authGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (authService.currentUserValue) {
    // Vérifier le rôle si spécifié dans la route
    const expectedRoles = route.data?.['roles'] as string[];
    if (expectedRoles && expectedRoles.length > 0) {
      const userRole = authService.currentUserValue.role;
      if (!expectedRoles.includes(userRole)) {
        // Pas le bon rôle, redirection vers dashboard
        router.navigate(['/dashboard']);
        return false;
      }
    }
    return true;
  }

  // Non connecté, redirection vers login avec URL de retour
  router.navigate(['/login'], { queryParams: { returnUrl: state.url } });
  return false;
};

// Guard pour les pages invités (login, register) - redirige si déjà connecté
export const guestGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (!authService.currentUserValue) {
    return true;
  }

  // Déjà connecté, redirection vers dashboard
  router.navigate(['/dashboard']);
  return false;
};

// Guard spécifique pour les admins
export const adminGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (authService.currentUserValue && authService.isAdmin()) {
    return true;
  }

  if (authService.currentUserValue) {
    // Connecté mais pas admin
    router.navigate(['/dashboard']);
  } else {
    // Non connecté
    router.navigate(['/login'], { queryParams: { returnUrl: state.url } });
  }
  return false;
};