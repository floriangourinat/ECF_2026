import { Routes } from '@angular/router';
import { LoginComponent } from './pages/login/login'; // Import du composant Login (nom court)
import { DashboardComponent } from './pages/dashboard/dashboard'; // Import du Dashboard (nom court corrigé)

export const routes: Routes = [
    // Redirection par défaut : redirige vers login
    { path: '', redirectTo: 'login', pathMatch: 'full' },
    
    // Page de connexion
    { path: 'login', component: LoginComponent },
    
    // Page d'accueil (Dashboard)
    { path: 'home', component: DashboardComponent }
];