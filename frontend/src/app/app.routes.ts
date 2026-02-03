import { Routes } from '@angular/router';
import { authGuard, guestGuard } from './_guards/auth.guard';

export const routes: Routes = [
  { 
    path: '', 
    redirectTo: 'home', 
    pathMatch: 'full' 
  },

  // PAGES PUBLIQUES
  { 
    path: 'home', 
    loadComponent: () => import('./pages/home/home').then(m => m.HomeComponent)
  },
  { 
    path: 'quote-request', 
    loadComponent: () => import('./pages/quote-request/quote-request').then(m => m.QuoteRequestComponent)
  },
  { 
    path: 'legal-notice', 
    loadComponent: () => import('./pages/legal-notice/legal-notice').then(m => m.LegalNoticeComponent)
  },
  { 
    path: 'cgu', 
    loadComponent: () => import('./pages/cgu/cgu').then(m => m.CguComponent)
  },
  { 
    path: 'cgv', 
    loadComponent: () => import('./pages/cgv/cgv').then(m => m.CgvComponent)
  },

  // AUTHENTIFICATION
  { 
    path: 'login', 
    loadComponent: () => import('./pages/login/login').then(m => m.LoginComponent),
    canActivate: [guestGuard]
  },
  { 
    path: 'register', 
    loadComponent: () => import('./pages/register/register').then(m => m.RegisterComponent),
    canActivate: [guestGuard]
  },
  { 
    path: 'forgot-password', 
    loadComponent: () => import('./pages/forgot-password/forgot-password').then(m => m.ForgotPasswordComponent),
    canActivate: [guestGuard]
  },

  // ESPACE CONNECTÉ
  { 
    path: 'dashboard', 
    loadComponent: () => import('./pages/dashboard/dashboard').then(m => m.DashboardComponent),
    canActivate: [authGuard]
  },

  // REDIRECTION 404
  { 
    path: '**', 
    redirectTo: 'home' 
  }
];