describe('Innov\'Events Mobile - Parcours Complet E2E', () => {

  const adminEmail = Cypress.env('ADMIN_EMAIL');
  const adminPassword = Cypress.env('ADMIN_PASSWORD');

  beforeEach(() => { cy.clearLocalStorage(); });

  describe('Page de connexion', () => {
    it('should display login page', () => {
      cy.visit('/');
      cy.contains('Innov\'Events').should('be.visible');
      cy.contains('Accès réservé aux administrateurs').should('be.visible');
    });

    it('should show error for empty fields', () => {
      cy.visit('/login');
      cy.contains('Se connecter').click();
      cy.contains('Veuillez remplir tous les champs').should('be.visible');
    });

    it('should login with admin account', () => {
      cy.visit('/login');
      cy.get('ion-input[type="email"] input').type(adminEmail);
      cy.get('ion-input[type="password"] input').type(adminPassword);
      cy.contains('Se connecter').click();
      cy.url({ timeout: 10000 }).should('include', '/tabs/events');
    });
  });

  describe('Événements (après login)', () => {
    beforeEach(() => {
      cy.visit('/login');
      cy.get('ion-input[type="email"] input').type(adminEmail);
      cy.get('ion-input[type="password"] input').type(adminPassword);
      cy.contains('Se connecter').click();
      cy.url({ timeout: 10000 }).should('include', '/tabs/events');
    });

    it('should display events list', () => {
      cy.contains('Événements à venir').should('be.visible');
    });

    it('should have tab navigation', () => {
      cy.get('ion-tab-bar').should('be.visible');
      cy.contains('CGU').should('be.visible');
      cy.contains('CGV').should('be.visible');
    });

    it('should navigate to CGU', () => {
      cy.contains('CGU').click();
      cy.contains('Conditions Générales d\'Utilisation').should('be.visible');
    });

    it('should navigate to CGV', () => {
      cy.contains('CGV').click();
      cy.contains('Conditions Générales de Vente').should('be.visible');
    });
  });

  describe('Détail événement + notes', () => {
    beforeEach(() => {
      cy.visit('/login');
      cy.get('ion-input[type="email"] input').type(adminEmail);
      cy.get('ion-input[type="password"] input').type(adminPassword);
      cy.contains('Se connecter').click();
      cy.url({ timeout: 10000 }).should('include', '/tabs/events');
    });

    it('should open event detail', () => {
      cy.get('ion-item.event-card', { timeout: 10000 }).first().click();
      cy.contains('Détail événement').should('be.visible');
      cy.contains('Voir fiche client').should('be.visible');
    });

    it('should add a note', () => {
      cy.get('ion-item.event-card', { timeout: 10000 }).first().click();
      cy.get('ion-textarea textarea').type('Note test E2E Cypress');
      cy.contains('Ajouter la note').click();
      cy.contains('Note test E2E Cypress', { timeout: 5000 }).should('be.visible');
    });
  });

  describe('Fiche client', () => {
    beforeEach(() => {
      cy.visit('/login');
      cy.get('ion-input[type="email"] input').type(adminEmail);
      cy.get('ion-input[type="password"] input').type(adminPassword);
      cy.contains('Se connecter').click();
      cy.url({ timeout: 10000 }).should('include', '/tabs/events');
    });

    it('should navigate to client from event', () => {
      cy.get('ion-item.event-card', { timeout: 10000 }).first().click();
      cy.contains('Voir fiche client').click();
      cy.contains('Fiche client').should('be.visible');
      cy.contains('Actions rapides').should('be.visible');
    });
  });

  describe('Déconnexion', () => {
    it('should logout', () => {
      cy.visit('/login');
      cy.get('ion-input[type="email"] input').type(adminEmail);
      cy.get('ion-input[type="password"] input').type(adminPassword);
      cy.contains('Se connecter').click();
      cy.url({ timeout: 10000 }).should('include', '/tabs/events');
      cy.contains('Déconnexion').click();
      cy.url({ timeout: 5000 }).should('include', '/login');
    });
  });
});