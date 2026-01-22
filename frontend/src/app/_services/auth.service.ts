import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, map } from 'rxjs';

// AJOUT DE 'export' ICI (Indispensable pour que le Dashboard puisse l'utiliser)
export interface User {
    id: number;
    email: string;
    role: string;
    token?: string;
    nom?: string;
    prenom?: string;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  // Vérifiez bien que le port est 8080
  private apiUrl = 'http://localhost:8080/api/auth/login.php';

  private currentUserSubject: BehaviorSubject<User | null>;
  public currentUser: Observable<User | null>;

  constructor(private http: HttpClient) {
    const storedUser = localStorage.getItem('currentUser');
    this.currentUserSubject = new BehaviorSubject<User | null>(storedUser ? JSON.parse(storedUser) : null);
    this.currentUser = this.currentUserSubject.asObservable();
  }

  // Voici la propriété que le Dashboard cherche (sans parenthèses)
  public get currentUserValue(): User | null {
    return this.currentUserSubject.value;
  }

  login(email: string, password: string): Observable<User> {
    return this.http.post<any>(this.apiUrl, { email, password })
      .pipe(map(response => {
        // Adaptation selon ce que renvoie votre PHP
        if (response && (response.token || response.user)) {
            const userData = response.user || response; // Fallback sécurité
            const user: User = {
                ...userData,
                token: response.token
            };
            
            localStorage.setItem('currentUser', JSON.stringify(user));
            this.currentUserSubject.next(user);
            return user;
        }
        return response;
      }));
  }

  logout() {
    localStorage.removeItem('currentUser');
    this.currentUserSubject.next(null);
  }
}