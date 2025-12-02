/**
 * A.7.4.2: RouteAnnouncer für Next.js
 * 
 * Screenreader-Unterstützung bei Client-Side-Navigation.
 * Kündigt Routenwechsel an und setzt Fokus auf main-Content.
 * 
 * Verwendung in _app.tsx:
 * ```tsx
 * <RouteAnnouncer />
 * ```
 */

import React, { useEffect, useState } from 'react';
import { useRouter } from 'next/router';

export const RouteAnnouncer: React.FC = () => {
  const router = useRouter();
  const [announcement, setAnnouncement] = useState('');

  useEffect(() => {
    const handleRouteChange = () => {
      setTimeout(() => {
        const title = document.title || 'Neue Seite geladen';
        setAnnouncement(`Navigiert zu: ${title}`);

        // A.7.4.2: Fokus auf main-Content setzen
        const main = document.getElementById('main-content');
        if (main) {
          (main as HTMLElement).focus();
        }
      }, 100);
    };

    router.events.on('routeChangeComplete', handleRouteChange);
    
    return () => {
      router.events.off('routeChangeComplete', handleRouteChange);
    };
  }, [router]);

  return (
    <div
      role="status"
      aria-live="polite"
      aria-atomic="true"
      className="sr-only"
      style={{
        position: 'absolute',
        width: '1px',
        height: '1px',
        padding: 0,
        margin: '-1px',
        overflow: 'hidden',
        clip: 'rect(0, 0, 0, 0)',
        whiteSpace: 'nowrap',
        borderWidth: 0,
      }}
    >
      {announcement}
    </div>
  );
};

