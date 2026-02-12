// Cypress E2E support file
Cypress.on('uncaught:exception', (err) => {
  // Ignorer les erreurs Angular non critiques
  if (err.message.includes('ResizeObserver') || err.message.includes('ChunkLoadError')) {
    return false;
  }
});
