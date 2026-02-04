import { Routes } from '@angular/router';
import { authGuard, guestGuard, adminGuard } from './_guards/auth.guard';

export const routes: Routes = [
  { 
    path: '', 
    redirectTo: 'home', 
    pathMatch: 'full' 
  },

  // ========== PAGES PUBLIQUES ==========
  { 
    path: 'home', 
    loadComponent: () => import('./pages/home/home').then(m => m.HomeComponent)
  },
  { 
    path: 'events', 
    loadComponent: () => import('./pages/events/events').then(m => m.EventsComponent)
  },
  { 
    path: 'events/:id', 
    loadComponent: () => import('./pages/event-detail/event-detail').then(m => m.EventDetailComponent)
  },
  { 
    path: 'reviews', 
    loadComponent: () => import('./pages/reviews/reviews').then(m => m.ReviewsComponent)
  },
  { 
    path: 'contact', 
    loadComponent: () => import('./pages/contact/contact').then(m => m.ContactComponent)
  },
  { 
    path: 'quote-request', 
    loadComponent: () => import('./pages/quote-request/quote-request').then(m => m.QuoteRequestComponent)
  },

  // ========== PAGES LÉGALES ==========
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

  // ========== AUTHENTIFICATION ==========
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

  // ========== ESPACE CONNECTÉ (Redirection selon rôle) ==========
  { 
    path: 'dashboard', 
    loadComponent: () => import('./pages/dashboard/dashboard').then(m => m.DashboardComponent),
    canActivate: [authGuard]
  },

  // ========== ESPACE ADMIN ==========
  { 
    path: 'admin/dashboard', 
    loadComponent: () => import('./pages/admin/dashboard/admin-dashboard').then(m => m.AdminDashboardComponent),
    canActivate: [adminGuard]
  },
  { 
    path: 'admin/prospects', 
    loadComponent: () => import('./pages/admin/prospects/prospects-list').then(m => m.ProspectsListComponent),
    canActivate: [adminGuard]
  },
  { 
    path: 'admin/prospects/:id', 
    loadComponent: () => import('./pages/admin/prospects/prospect-detail').then(m => m.ProspectDetailComponent),
    canActivate: [adminGuard]
  },

  // ========== REDIRECTION 404 ==========
  { 
    path: '**', 
    redirectTo: 'home' 
  }
];