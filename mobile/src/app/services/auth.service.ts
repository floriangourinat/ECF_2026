import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, map } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private currentUserSubject: BehaviorSubject<any>;
  public currentUser: Observable<any>;

  constructor(private http: HttpClient) {
    const stored = localStorage.getItem('mobileUser');
    this.currentUserSubject = new BehaviorSubject<any>(stored ? JSON.parse(stored) : null);
    this.currentUser = this.currentUserSubject.asObservable();
  }

  get currentUserValue() { return this.currentUserSubject.value; }

  login(email: string, password: string): Observable<any> {
    return this.http.post<any>(`${environment.apiUrl}/auth/login.php`, { email, password })
      .pipe(map(r => {
        if (r && r.user) {
          const user = { ...r.user, token: r.token };
          localStorage.setItem('mobileUser', JSON.stringify(user));
          this.currentUserSubject.next(user);
          return user;
        }
        throw new Error(r.message || 'Erreur de connexion');
      }));
  }

  logout(): void {
    localStorage.removeItem('mobileUser');
    this.currentUserSubject.next(null);
  }

  isLoggedIn(): boolean { return !!this.currentUserValue; }
}
