import { ApplicationConfig, provideZoneChangeDetection } from '@angular/core';
import { provideRouter } from '@angular/router';
import { provideHttpClient, withFetch, withInterceptors } from '@angular/common/http';

import { routes } from './app.routes';
import { htmlEntityDecoderInterceptor } from './_interceptors/html-entity-decoder.interceptor';
import { authInterceptor } from './_interceptors/auth.interceptor';

export const appConfig: ApplicationConfig = {
  providers: [
    provideZoneChangeDetection({ eventCoalescing: true }),
    provideRouter(routes),
    provideHttpClient(withFetch(), withInterceptors([
      htmlEntityDecoderInterceptor,
      authInterceptor
    ]))
  ]
};
