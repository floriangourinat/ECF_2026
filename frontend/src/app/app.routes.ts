import { Routes } from '@angular/router';
// Note : l'import pointe vers le dossier ou le fichier 'login' sans l'extension
import { LoginComponent } from './pages/login/login'; 

export const routes: Routes = [
    { path: '', redirectTo: 'login', pathMatch: 'full' },
    { path: 'login', component: LoginComponent }
];