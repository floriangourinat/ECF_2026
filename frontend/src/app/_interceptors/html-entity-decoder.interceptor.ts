import { HttpEvent, HttpHandlerFn, HttpInterceptorFn, HttpResponse } from '@angular/common/http';
import { Observable, map } from 'rxjs';

function decodeHtmlEntities(value: string): string {
  if (!value || !value.includes('&')) return value;

  const textarea = document.createElement('textarea');
  textarea.innerHTML = value;
  return textarea.value;
}

function decodeBodyDeep(value: unknown): unknown {
  if (typeof value === 'string') {
    return decodeHtmlEntities(value);
  }

  if (Array.isArray(value)) {
    return value.map((item) => decodeBodyDeep(item));
  }

  if (value && typeof value === 'object') {
    const decoded: Record<string, unknown> = {};
    for (const [key, nestedValue] of Object.entries(value as Record<string, unknown>)) {
      decoded[key] = decodeBodyDeep(nestedValue);
    }
    return decoded;
  }

  return value;
}

export const htmlEntityDecoderInterceptor: HttpInterceptorFn = (
  req,
  next: HttpHandlerFn
): Observable<HttpEvent<unknown>> => {
  return next(req).pipe(
    map((event: HttpEvent<unknown>) => {
      if (event instanceof HttpResponse) {
        return event.clone({ body: decodeBodyDeep(event.body) });
      }
      return event;
    })
  );
};
