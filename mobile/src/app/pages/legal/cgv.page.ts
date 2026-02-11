import { Component } from '@angular/core';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton
} from '@ionic/angular/standalone';

@Component({
  selector: 'app-cgv',
  standalone: true,
  imports: [IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-title>Conditions Générales de Vente</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      <div class="legal-content">
        <h2>Conditions Générales de Vente</h2>
        <p><strong>Dernière mise à jour : Février 2026</strong></p>

        <h3>Article 1 — Identification du prestataire</h3>
        <p>Innov'Events — Société spécialisée dans l'organisation d'événements professionnels haut de gamme (séminaires, conférences, team-buildings). Siège social : Paris, France. Email : contact@innovevents.com.</p>

        <h3>Article 2 — Objet</h3>
        <p>Les présentes Conditions Générales de Vente (CGV) définissent les droits et obligations des parties dans le cadre de la prestation de services événementiels proposés par Innov'Events à ses clients.</p>

        <h3>Article 3 — Devis et commande</h3>
        <p>Tout projet fait l'objet d'un devis détaillé établi par Innov'Events. Le devis est valable 30 jours à compter de sa date d'émission. La commande est considérée comme ferme et définitive à réception de l'acceptation écrite du devis par le client, que ce soit par voie électronique via l'application ou par courrier.</p>

        <h3>Article 4 — Tarifs et paiement</h3>
        <p>Les prix indiqués sont en euros hors taxes (HT). La TVA applicable (20%) sera ajoutée au montant HT. Le règlement s'effectue selon les modalités définies dans le devis. Un acompte de 30% est exigé à la signature du devis, le solde étant dû au plus tard 15 jours après la tenue de l'événement.</p>

        <h3>Article 5 — Annulation</h3>
        <p>En cas d'annulation par le client : plus de 60 jours avant l'événement, l'acompte versé sera restitué à hauteur de 50%. Entre 30 et 60 jours, l'acompte est retenu intégralement. Moins de 30 jours avant la date prévue, la totalité du montant du devis est due.</p>

        <h3>Article 6 — Obligations d'Innov'Events</h3>
        <p>Innov'Events s'engage à mettre en œuvre tous les moyens nécessaires à la bonne réalisation des prestations définies dans le devis. Innov'Events s'engage à respecter les délais convenus et à informer le client de tout imprévu pouvant impacter le déroulement de l'événement.</p>

        <h3>Article 7 — Responsabilité</h3>
        <p>La responsabilité d'Innov'Events est limitée au montant du devis accepté. Innov'Events ne saurait être tenue responsable des dommages indirects, des cas de force majeure ou des défaillances de prestataires tiers intervenant dans l'organisation de l'événement.</p>

        <h3>Article 8 — Confidentialité</h3>
        <p>Chaque partie s'engage à ne pas divulguer les informations confidentielles de l'autre partie obtenues dans le cadre de leur collaboration, conformément au RGPD et aux lois en vigueur.</p>

        <h3>Article 9 — Litiges</h3>
        <p>En cas de litige, les parties s'engagent à rechercher une solution amiable avant toute action judiciaire. À défaut d'accord, le litige sera soumis aux tribunaux compétents de Paris, nonobstant pluralité de défendeurs ou appel en garantie.</p>

        <p class="footer">© 2026 Innov'Events — Tous droits réservés.</p>
      </div>
    </ion-content>
  `,
  styles: [`
    .legal-content {
      max-width: 600px; margin: 0 auto;
      h2 { color: #2c3e50; font-size: 1.3rem; margin-bottom: 5px; }
      h3 { color: #f39c12; font-size: 1rem; margin-top: 20px; }
      p { color: #555; font-size: 0.9rem; line-height: 1.6; }
      .footer { text-align: center; margin-top: 30px; color: #999; font-size: 0.8rem; }
    }
  `]
})
export class CgvPage {}
