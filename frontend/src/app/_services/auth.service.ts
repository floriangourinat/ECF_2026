import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';

// Définition de l'interface User pour le typage strict des données
export interface User {
  id: number;
  nom: string;
  prenom: string;
  role: 'ADMIN' | 'EMPLOYE' | 'CLIENT';
}

@Injectable({
  providedIn: 'root' // Service accessible globalement dans l'application
})
export class AuthService {

  // URL de l'API d'authentification (Backend PHP)
  private apiUrl = 'http://localhost/ECF_2026/backend/api/auth/login.php';

  // Clé utilisée pour stocker les informations de l'utilisateur dans le localStorage
  private userKey = 'current_user'; 

  constructor(private http: HttpClient) { }

  /**
   * Envoi de la requête de connexion au serveur
   * @param email Email de l'utilisateur
   * @param mot_de_passe Mot de passe en clair
   */
  login(email: string, mot_de_passe: string): Observable<any> {
    return this.http.post<any>(this.apiUrl, { email, mot_de_passe })
      .pipe(
        // Interception de la réponse pour sauvegarder l'utilisateur si la connexion réussit
        tap(response => {
          if (response && response.user) {
            this.saveUser(response.user);
          }
        })
      );
  }

  /**
   * Sauvegarde de l'objet utilisateur dans le stockage local du navigateur
   * Permet de maintenir la session active après rafraîchissement
   */
  private saveUser(user: User): void {
    localStorage.setItem(this.userKey, JSON.stringify(user));
  }

  /**
   * Récupération des informations de l'utilisateur connecté
   */
  getUser(): User | null {
    const userStr = localStorage.getItem(this.userKey);
    if (userStr) return JSON.parse(userStr);
    return null;
  }

  /**
   * Vérification de l'état de connexion (retourne vrai si un utilisateur est stocké)
   */
  isLogged(): boolean {
    return !!localStorage.getItem(this.userKey);
  }

  /**
   * Déconnexion de l'utilisateur et nettoyage du stockage local
   */
  logout(): void {
    localStorage.removeItem(this.userKey);
  }
}