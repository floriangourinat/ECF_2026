import { inject } from '@angular/core';
import { Router, CanActivateFn } from '@angular/router';
import { AuthService } from '../_services/auth.service';

// Guard pour les pages protégées (nécessite connexion)
export const authGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (authService.currentUserValue) {
    const expectedRoles = route.data?.['roles'] as string[];
    if (expectedRoles && expectedRoles.length > 0) {
      const userRole = authService.currentUserValue.role;
      if (!expectedRoles.includes(userRole)) {
        router.navigate(['/dashboard']);
        return false;
      }
    }
    return true;
  }

  router.navigate(['/login'], { queryParams: { returnUrl: state.url } });
  return false;
};

// Guard pour les pages invités (login, register)
export const guestGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (!authService.currentUserValue) {
    return true;
  }

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
    router.navigate(['/dashboard']);
  } else {
    router.navigate(['/login'], { queryParams: { returnUrl: state.url } });
  }
  return false;
};

// Guard spécifique pour les employés (employé OU admin)
export const employeeGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  const user = authService.currentUserValue;
  if (user && (user.role === 'employee' || user.role === 'admin')) {
    return true;
  }

  if (user) {
    router.navigate(['/dashboard']);
  } else {
    router.navigate(['/login'], { queryParams: { returnUrl: state.url } });
  }
  return false;
};
