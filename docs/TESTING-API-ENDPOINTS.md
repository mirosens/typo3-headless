# Testing API-Endpunkte

## Problem: Page Not Found

Wenn Sie "Page Not Found" erhalten, kann das mehrere Ursachen haben:

### 1. TypoScript-Template nicht zugewiesen

**Lösung**: Im TYPO3 Backend:
1. Zu "Template" → "Template" navigieren
2. Die Root-Seite (meist UID 1) auswählen
3. Prüfen, ob ein Template zugewiesen ist
4. Falls nicht: Template zuweisen oder erstellen

### 2. TypoScript-Konfiguration nicht geladen

**Prüfung**: Im Backend unter "Template" → "Info/Modify":
- Prüfen, ob `fahn_core` Extension geladen ist
- Prüfen, ob `setup.typoscript` geladen wird

### 3. Korrekte URL-Formatierung für curl

Die eckigen Klammern müssen URL-encodiert werden:

```bash
# Option 1: URL-Encoding
curl "https://fahn-core-typo3.ddev.site/?tx_fahncorefahndung_api%5Baction%5D=list&page=1&limit=10"

# Option 2: Mit --data-urlencode (GET-Parameter)
curl -G "https://fahn-core-typo3.ddev.site/" \
  --data-urlencode "tx_fahncorefahndung_api[action]=list" \
  --data-urlencode "page=1" \
  --data-urlencode "limit=10"

# Option 3: Einfache Anführungszeichen (verhindert Shell-Interpretation)
curl 'https://fahn-core-typo3.ddev.site/?tx_fahncorefahndung_api[action]=list&page=1&limit=10'
```

### 4. DDEV-URL verwenden

Statt `localhost:8080` die DDEV-URL verwenden:
- `https://fahn-core-typo3.ddev.site`
- oder `http://fahn-core-typo3.ddev.site`

### 5. TypoScript-Cache prüfen

Falls Änderungen nicht sichtbar sind:
```bash
ddev exec vendor/bin/typo3 cache:flush
```

## Erwartete Antworten

### Erfolgreiche Liste-API:
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "page": 1,
    "limit": 10,
    "total": 0,
    "totalPages": 0
  }
}
```

### Session-Status:
```json
{
  "authenticated": false,
  "user": null
}
```

## Debugging

Falls weiterhin Probleme bestehen:

1. **Backend-Log prüfen**: `var/log/typo3_*.log`
2. **TypoScript-Objekt-Browser** im Backend nutzen
3. **Template-Analyse** im Backend durchführen


