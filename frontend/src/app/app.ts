import { Component, inject } from '@angular/core';
import { Title } from '@angular/platform-browser';
import { ActivatedRoute, NavigationEnd, Router, RouterOutlet } from '@angular/router';
import { filter } from 'rxjs';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet],
  templateUrl: './app.html',
  styleUrl: './app.scss'
})
export class AppComponent {
  private readonly router = inject(Router);
  private readonly activatedRoute = inject(ActivatedRoute);
  private readonly titleService = inject(Title);

  private readonly segmentTitles: Record<string, string> = {
    home: 'Accueil',
    events: 'Ã‰vÃ©nements',
    reviews: 'Avis',
    contact: 'Contact',
    'quote-request': 'Demande de devis',
    'legal-notice': 'Mentions lÃ©gales',
    cgu: 'CGU',
    cgv: 'CGV',
    login: 'Connexion',
    register: 'Inscription',
    'forgot-password': 'Mot de passe oubliÃ©',
    dashboard: 'Tableau de bord',
    client: 'Espace client',
    employee: 'Espace employÃ©',
    admin: 'Espace admin',
    clients: 'Clients',
    prospects: 'Prospects',
    quotes: 'Devis',
    employees: 'EmployÃ©s',
    logs: 'Logs',
    profile: 'Profil',
    'change-password': 'Changer le mot de passe',
    create: 'CrÃ©er',
    edit: 'Modifier',
    detail: 'DÃ©tail'
  };

  constructor() {
    this.router.events.pipe(filter((event) => event instanceof NavigationEnd)).subscribe(() => {
      this.titleService.setTitle(`${this.resolvePageTitle()} | Innov'Events`);
    });
  }

  private resolvePageTitle(): string {
    let currentRoute = this.activatedRoute;

    while (currentRoute.firstChild) {
      currentRoute = currentRoute.firstChild;
    }

    const configuredTitle = currentRoute.snapshot.routeConfig?.title;
    if (typeof configuredTitle === 'string' && configuredTitle.trim()) {
      return configuredTitle;
    }

    const segments = this.router.url
      .split('?')[0]
      .split('/')
      .filter((segment) => segment.length > 0);

    const meaningfulSegment = [...segments]
      .reverse()
      .find((segment) => !/^\d+$/.test(segment));

    if (!meaningfulSegment) {
      return 'Accueil';
    }

    return this.segmentTitles[meaningfulSegment] ?? this.toReadableLabel(meaningfulSegment);
  }

  private toReadableLabel(segment: string): string {
    return segment
      .replace(/[-_]/g, ' ')
      .replace(/\b\w/g, (letter) => letter.toUpperCase());
  }
}
