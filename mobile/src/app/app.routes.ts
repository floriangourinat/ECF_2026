import { Routes } from '@angular/router';

export const routes: Routes = [
  { path: '', redirectTo: 'login', pathMatch: 'full' },
  {
    path: 'login',
    loadComponent: () => import('./pages/login/login.page').then(m => m.LoginPage)
  },
  {
    path: 'tabs',
    loadComponent: () => import('./pages/tabs/tabs.page').then(m => m.TabsPage),
    children: [
      { path: '', redirectTo: 'events', pathMatch: 'full' },
      {
        path: 'events',
        loadComponent: () => import('./pages/events/events.page').then(m => m.EventsPage)
      },
      {
        path: 'cgu',
        loadComponent: () => import('./pages/legal/cgu.page').then(m => m.CguPage)
      },
      {
        path: 'cgv',
        loadComponent: () => import('./pages/legal/cgv.page').then(m => m.CgvPage)
      }
    ]
  },
  {
    path: 'event/:id',
    loadComponent: () => import('./pages/event-detail/event-detail.page').then(m => m.EventDetailPage)
  },
  {
    path: 'client/:id',
    loadComponent: () => import('./pages/client-detail/client-detail.page').then(m => m.ClientDetailPage)
  }
];
