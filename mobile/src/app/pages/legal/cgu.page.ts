import { Component } from '@angular/core';
import { IonHeader, IonToolbar, IonTitle, IonContent } from '@ionic/angular/standalone';

@Component({
  selector: 'app-cgu',
  standalone: true,
  imports: [IonHeader, IonToolbar, IonTitle, IonContent],
  template: `
    <ion-header><ion-toolbar><ion-title>Conditions Générales d'Utilisation</ion-title></ion-toolbar></ion-header>
    <ion-content class="ion-padding">
      <div class="legal-content">
        <h2>Conditions Générales d'Utilisation</h2>
        <p><strong>Dernière mise à jour : Février 2026</strong></p>
        <h3>Article 1 — Objet</h3>
        <p>Les présentes Conditions Générales d'Utilisation (CGU) régissent l'accès et l'utilisation de l'application mobile « Innov'Events Manager », éditée par la société Innov'Events. L'utilisation de l'application implique l'acceptation pleine et entière des présentes CGU.</p>
        <h3>Article 2 — Accès à l'application</h3>
        <p>L'application est accessible aux administrateurs d'Innov'Events disposant d'identifiants de connexion valides. L'accès est personnel et non cessible. L'utilisateur est responsable de la confidentialité de ses identifiants.</p>
        <h3>Article 3 — Services proposés</h3>
        <p>L'application permet la consultation des événements, des fiches clients, l'ajout de notes collaboratives et la communication rapide avec les clients (appel, email, itinéraire). Ces services sont fournis à titre professionnel dans le cadre de l'activité d'Innov'Events.</p>
        <h3>Article 4 — Données personnelles</h3>
        <p>Conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés, les données collectées sont traitées dans le cadre de la gestion des événements. L'utilisateur dispose d'un droit d'accès, de rectification et de suppression de ses données en contactant le responsable de traitement à l'adresse : contact&#64;innovevents.com.</p>
        <h3>Article 5 — Propriété intellectuelle</h3>
        <p>L'ensemble des éléments de l'application (design, code, textes, logos) sont la propriété exclusive d'Innov'Events. Toute reproduction, même partielle, est interdite sans autorisation préalable.</p>
        <h3>Article 6 — Responsabilité</h3>
        <p>Innov'Events s'efforce d'assurer la disponibilité de l'application mais ne saurait être tenue responsable des interruptions de service, pertes de données ou dommages indirects liés à l'utilisation de l'application.</p>
        <h3>Article 7 — Modification des CGU</h3>
        <p>Innov'Events se réserve le droit de modifier les présentes CGU à tout moment. Les utilisateurs seront informés de toute modification substantielle.</p>
        <h3>Article 8 — Droit applicable</h3>
        <p>Les présentes CGU sont soumises au droit français. En cas de litige, les tribunaux compétents de Paris seront seuls compétents.</p>
        <p class="footer">© 2026 Innov'Events — Tous droits réservés.</p>
      </div>
    </ion-content>
  `,
  styles: [`.legal-content { max-width:600px; margin:0 auto; h2 { color:#2c3e50; font-size:1.3rem; } h3 { color:#f39c12; font-size:1rem; margin-top:20px; } p { color:#555; font-size:0.9rem; line-height:1.6; } .footer { text-align:center; margin-top:30px; color:#999; font-size:0.8rem; } }`]
})
export class CguPage {}
