import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, map } from 'rxjs';

export interface User {
  id: number;
  email: string;
  role: string;
  token?: string;
  last_name?: string;
  first_name?: string;
  username?: string;
  must_change_password?: boolean;
}

export interface RegisterData {
  email: string;
  password: string;
  last_name: string;
  first_name: string;
  username: string;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private baseUrl = 'http://localhost:8080/api/auth';

  private currentUserSubject: BehaviorSubject<User | null>;
  public currentUser: Observable<User | null>;

  constructor(private http: HttpClient) {
    const storedUser = localStorage.getItem('currentUser');
    this.currentUserSubject = new BehaviorSubject<User | null>(
      storedUser ? JSON.parse(storedUser) : null
    );
    this.currentUser = this.currentUserSubject.asObservable();
  }

  public get currentUserValue(): User | null {
    return this.currentUserSubject.value;
  }

  login(email: string, password: string): Observable<User> {
    return this.http.post<any>(`${this.baseUrl}/login.php`, { email, password })
      .pipe(map(response => {
        if (response && response.user) {
          const user: User = {
            id: response.user.id,
            email: response.user.email,
            role: response.user.role,
            last_name: response.user.last_name,
            first_name: response.user.first_name,
            username: response.user.username,
            must_change_password: response.user.must_change_password,
            token: response.token
          };
          localStorage.setItem('currentUser', JSON.stringify(user));
          this.currentUserSubject.next(user);
          return user;
        }
        return response;
      }));
  }

  register(userData: RegisterData): Observable<any> {
    return this.http.post<any>(`${this.baseUrl}/register.php`, userData);
  }

  forgotPassword(email: string): Observable<any> {
    return this.http.post<any>(`${this.baseUrl}/forgot-password.php`, { email });
  }

  changePassword(currentPassword: string, newPassword: string): Observable<any> {
    return this.http.post<any>(`${this.baseUrl}/change-password.php`, {
      current_password: currentPassword,
      new_password: newPassword
    });
  }

  logout(): void {
    localStorage.removeItem('currentUser');
    this.currentUserSubject.next(null);
  }

  isLoggedIn(): boolean {
    return !!this.currentUserValue;
  }

  hasRole(role: string): boolean {
    return this.currentUserValue?.role === role;
  }

  isAdmin(): boolean {
    return this.hasRole('admin');
  }

  isEmployee(): boolean {
    return this.hasRole('employee') || this.hasRole('admin');
  }

  isClient(): boolean {
    return this.hasRole('client');
  }

  getToken(): string | null {
    return this.currentUserValue?.token || null;
  }
}