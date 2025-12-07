# A.7 – Accessibility-Komponenten für Next.js

Dieses Verzeichnis enthält produktionsreife Accessibility-Komponenten für das FAHN-CORE Next.js Frontend.

## Komponenten

### AccessibleImage.tsx
Single-Entry-Point für alle Bilder mit automatischer WCAG 2.2-konformer Behandlung.

**Verwendung:**
```tsx
import { AccessibleImage } from '@/components/atoms/AccessibleImage';

<AccessibleImage 
  data={imageDto} 
  className="my-image" 
  priority 
/>
```

### RouteAnnouncer.tsx
Screenreader-Unterstützung bei Client-Side-Navigation.

**Verwendung in `_app.tsx`:**
```tsx
import { RouteAnnouncer } from '@/components/utils/RouteAnnouncer';

function MyApp({ Component, pageProps }) {
  return (
    <>
      <RouteAnnouncer />
      <Component {...pageProps} />
    </>
  );
}
```

### SkipLinks.tsx
Skip-Links für Keyboard-Navigation.

**Verwendung in Layout:**
```tsx
import { SkipLinks } from '@/components/utils/SkipLinks';

<SkipLinks />
```

## QA-Tools

### ESLint (eslint-plugin-jsx-a11y)
Strikte Accessibility-Regeln für React/Next.js.

**Installation:**
```bash
pnpm add -D eslint-plugin-jsx-a11y
```

**Verwendung:**
Die `.eslintrc.json` kann in das Next.js-Projekt kopiert werden oder als Basis dienen.

### pa11y-ci
Automatisierte Accessibility-Tests im CI/CD.

**Installation:**
```bash
pnpm add -D pa11y-ci
```

**Verwendung:**
```bash
pa11y-ci --config pa11y.config.json
```

## Integration in Next.js-Projekt

1. Kopiere die Komponenten in `components/atoms/` bzw. `components/utils/`
2. Installiere Abhängigkeiten:
   ```bash
   pnpm add next eslint-plugin-jsx-a11y
   pnpm add -D pa11y-ci
   ```
3. Integriere `.eslintrc.json` in die ESLint-Konfiguration
4. Füge `pa11y.config.json` ins Projekt-Root ein
5. Integriere `RouteAnnouncer` in `_app.tsx`
6. Stelle sicher, dass im Layout ein `<main id="main-content" tabIndex={-1}>` existiert

## WCAG 2.2 Konformität

- ✅ SC 1.1.1 (Non-text Content): Alt-Texte für alle informativen Bilder
- ✅ SC 2.4.1 (Bypass Blocks): Skip-Links implementiert
- ✅ SC 2.4.2 (Page Titled): RouteAnnouncer kündigt Seitentitel an
- ✅ SC 4.1.2 (Name, Role, Value): ARIA-Attribute korrekt gesetzt









