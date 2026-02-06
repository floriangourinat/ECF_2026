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
  { 
    path: 'admin/clients', 
    loadComponent: () => import('./pages/admin/clients/clients-list').then(m => m.ClientsListComponent),
    canActivate: [adminGuard]
  },
  { 
    path: 'admin/clients/:id', 
    loadComponent: () => import('./pages/admin/clients/client-detail').then(m => m.ClientDetailComponent),
    canActivate: [adminGuard]
  },
  { 
    path: 'admin/clients/:id/edit', 
    loadComponent: () => import('./pages/admin/clients/client-edit').then(m => m.ClientEditComponent),
    canActivate: [adminGuard]
  },
  { 
    path: 'admin/events', 
    loadComponent: () => import('./pages/admin/events/events-list').then(m => m.EventsListComponent),
    canActivate: [adminGuard]
  },
  { 
    path: 'admin/events/:id', 
    loadComponent: () => import('./pages/admin/events/event-detail').then(m => m.AdminEventDetailComponent),
    canActivate: [adminGuard]
  },
  { 
    path: 'admin/events/:id/edit', 
    loadComponent: () => import('./pages/admin/events/event-edit').then(m => m.EventEditComponent),
    canActivate: [adminGuard]
  },
  { 
    path: 'admin/quotes', 
    loadComponent: () => import('./pages/admin/quotes/quotes-list').then(m => m.QuotesListComponent),
    canActivate: [adminGuard]
  },
  { 
    path: 'admin/quotes/create', 
    loadComponent: () => import('./pages/admin/quotes/quote-create').then(m => m.QuoteCreateComponent),
    canActivate: [adminGuard]
  },
  { 
    path: 'admin/quotes/:id', 
    loadComponent: () => import('./pages/admin/quotes/quote-detail').then(m => m.QuoteDetailComponent),
    canActivate: [adminGuard]
  },
  { 
    path: 'admin/employees', 
    loadComponent: () => import('./pages/admin/employees/employees-list').then(m => m.EmployeesListComponent),
    canActivate: [adminGuard]
  },
  { 
    path: 'admin/employees/:id', 
    loadComponent: () => import('./pages/admin/employees/employee-detail').then(m => m.EmployeeDetailComponent),
    canActivate: [adminGuard]
  },
  { 
    path: 'admin/reviews', 
    loadComponent: () => import('./pages/admin/reviews/reviews-list').then(m => m.AdminReviewsListComponent),
    canActivate: [adminGuard]
  },
  { 
    path: 'admin/logs', 
    loadComponent: () => import('./pages/admin/logs/logs-list').then(m => m.LogsListComponent),
    canActivate: [adminGuard]
  },

  // ========== REDIRECTION 404 ==========
  { 
    path: '**', 
    redirectTo: 'home' 
  }
];