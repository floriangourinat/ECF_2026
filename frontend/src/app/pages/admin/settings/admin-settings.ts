import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';

@Component({
  selector: 'app-admin-settings',
  standalone: true,
  imports: [CommonModule, FormsModule, AdminLayoutComponent],
  templateUrl: 'admin-settings.html',
  styleUrl: 'admin-settings.scss'
})
export class AdminSettingsComponent implements OnInit {
  loading = false;
  saving = false;
  error = '';
  success = '';

  quoteSuccessMessage = '';
  readonly defaultMessage = 'Merci pour votre demande. ChloÃ© vous recontactera dans les plus brefs dÃ©lais pour discuter de votre projet.';

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
    this.loadMessage();
  }

  loadMessage(): void {
    this.loading = true;
    this.error = '';

    this.http.get<any>('/api/settings/get_quote_success_message.php').subscribe({
      next: (response) => {
        this.quoteSuccessMessage = response?.message || this.defaultMessage;
        this.loading = false;
      },
      error: () => {
        this.quoteSuccessMessage = this.defaultMessage;
        this.error = 'Impossible de charger le message actuel.';
        this.loading = false;
      }
    });
  }

  save(): void {
    if (!this.quoteSuccessMessage || this.quoteSuccessMessage.trim().length < 10) {
      this.error = 'Le message doit contenir au moins 10 caractÃ¨res.';
      return;
    }

    this.saving = true;
    this.error = '';
    this.success = '';

    this.http.put<any>('/api/settings/update_quote_success_message.php', {
      message: this.quoteSuccessMessage.trim()
    }).subscribe({
      next: (response) => {
        this.success = response?.message || 'Message enregistrÃ©.';
        this.saving = false;
      },
      error: (err) => {
        this.error = err?.error?.message || 'Erreur lors de l\'enregistrement.';
        this.saving = false;
      }
    });
  }
}
