import { HttpInterceptorFn } from '@angular/common/http';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const stored = localStorage.getItem('mobileUser');

  if (!stored) {
    return next(req);
  }

  try {
    const user = JSON.parse(stored);
    const token = user?.token;

    if (!token) {
      return next(req);
    }

    const authReq = req.clone({
      setHeaders: {
        Authorization: `Bearer ${token}`
      }
    });

    return next(authReq);
  } catch {
    return next(req);
  }
};
