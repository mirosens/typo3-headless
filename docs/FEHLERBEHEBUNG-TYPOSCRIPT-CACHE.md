# Fehlerbehebung: TypoScript-Cache Problem

## Problem

**Fehlermeldung**:
```
TypoScript condition [request.getMethod() == "OPTIONS"] could not be evaluated: 
Unable to call method "getMethod" of object "TYPO3\CMS\Core\ExpressionLanguage\RequestWrapper".
```

## Ursache

Der TypoScript-Cache enthält noch eine alte Bedingung `[request.getMethod() == "OPTIONS"]`, die nicht mehr in der aktuellen `setup.typoscript` existiert. Diese Bedingung ist nicht mehr notwendig, da OPTIONS-Requests bereits von der `CorsMiddleware` behandelt werden.

## Lösung

### 1. TypoScript-Cache leeren

**Option A: Über TYPO3 Backend**
1. Im TYPO3 Backend einloggen
2. Zu "System" → "Wartung" → "Cache leeren" navigieren
3. "TypoScript Cache" auswählen und leeren

**Option B: Über CLI (DDEV)**
```bash
ddev exec vendor/bin/typo3 cache:flush --group typoScript
```

**Option C: Manuell (falls nötig)**
```bash
# Im DDEV-Container
ddev ssh
rm -rf var/cache/code/typo3/*
```

### 2. Verifizierung

Nach dem Leeren des Caches sollte die Seite wieder funktionieren. Die `CorsMiddleware` behandelt OPTIONS-Requests automatisch, daher ist keine TypoScript-Bedingung mehr notwendig.

## Warum keine TypoScript-Bedingung?

Die `CorsMiddleware` (in `packages/fahn_core/Classes/Middleware/CorsMiddleware.php`) fängt OPTIONS-Requests bereits ab, bevor sie TypoScript erreichen:

```php
// OPTIONS-Preflight: sofort 204 + CORS-Header, kein TSFE-Bootstrap
if ($method === 'OPTIONS') {
    $response = new Response('php://memory', 204);
    return $this->withCorsHeaders($response, $origin);
}
```

Daher ist eine TypoScript-Bedingung für OPTIONS-Requests nicht nur unnötig, sondern würde auch zu Problemen führen, da `request.getMethod()` in TYPO3 v13 nicht in TypoScript-Bedingungen verfügbar ist.

## Prävention

Um solche Probleme in Zukunft zu vermeiden:

1. **Immer Cache leeren** nach TypoScript-Änderungen
2. **Keine Request-Method-Prüfungen in TypoScript** - diese gehören in Middleware
3. **CORS-Handling über Middleware**, nicht über TypoScript

---

**Status**: ✅ Problem behoben durch Cache-Leerung


