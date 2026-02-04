import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { AdminLayoutComponent } from '../../../components/admin-layout/admin-layout';

interface Service {
  label: string;
  description: string;
  unit_price_ht: number;
}

interface Event {
  id: number;
  name: string;
  client_company: string;
  client_first_name: string;
  client_last_name: string;
}

@Component({
  selector: 'app-quote-create',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, AdminLayoutComponent],
  templateUrl: './quote-create.html',
  styleUrls: ['./quote-create.scss']
})
export class QuoteCreateComponent implements OnInit {
  events: Event[] = [];
  selectedEventId: string = '';
  taxRate: number = 20;
  services: Service[] = [{ label: '', description: '', unit_price_ht: 0 }];
  
  loading = false;
  error = '';

  constructor(
    private http: HttpClient,
    private router: Router,
    private route: ActivatedRoute
  ) {}

  ngOnInit(): void {
    this.loadEvents();
    
    // Pré-sélectionner l'événement si passé en paramètre
    this.route.queryParams.subscribe(params => {
      if (params['event_id']) {
        this.selectedEventId = params['event_id'];
      }
    });
  }

  loadEvents(): void {
    this.http.get<any>('http://localhost:8080/api/events/read_all.php').subscribe({
      next: (response) => {
        this.events = response.data || [];
      }
    });
  }

  addService(): void {
    this.services.push({ label: '', description: '', unit_price_ht: 0 });
  }

  removeService(index: number): void {
    if (this.services.length > 1) {
      this.services.splice(index, 1);
    }
  }

  get totalHT(): number {
    return this.services.reduce((sum, s) => sum + (s.unit_price_ht || 0), 0);
  }

  get totalTVA(): number {
    return this.totalHT * (this.taxRate / 100);
  }

  get totalTTC(): number {
    return this.totalHT + this.totalTVA;
  }

  createQuote(): void {
    if (!this.selectedEventId) {
      this.error = 'Veuillez sélectionner un événement';
      return;
    }

    const validServices = this.services.filter(s => s.label && s.unit_price_ht > 0);
    if (validServices.length === 0) {
      this.error = 'Ajoutez au moins une prestation avec un libellé et un montant';
      return;
    }

    this.loading = true;
    this.error = '';

    this.http.post<any>('http://localhost:8080/api/quotes/create.php', {
      event_id: this.selectedEventId,
      tax_rate: this.taxRate,
      services: validServices
    }).subscribe({
      next: (response) => {
        this.router.navigate(['/admin/quotes', response.data.id]);
      },
      error: (err) => {
        this.error = err.error?.message || 'Erreur lors de la création';
        this.loading = false;
      }
    });
  }

  formatCurrency(amount: number): string {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
  }
}