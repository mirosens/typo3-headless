# A.7.5.1 – Accessibility-Audit Command

## Verwendung

```bash
# TYPO3 CLI
vendor/bin/typo3 fahn-core:accessibility:audit

# Oder via Composer
composer exec typo3 fahn-core:accessibility:audit
```

## Funktionsweise

Der Command prüft alle `sys_file_reference`-Einträge auf:
- Fehlende Alt-Texte bei nicht-dekorativen Bildern
- Inkonsistenzen zwischen `tx_is_decorative` und `alternative`

## CI/CD-Integration

Der Command gibt bei Fehlern einen Exit-Code != 0 zurück, sodass CI/CD-Pipelines automatisch fehlschlagen:

```yaml
# Beispiel: GitHub Actions
- name: Accessibility Audit
  run: composer exec typo3 fahn-core:accessibility:audit
```

## Ausgabe

**Erfolg:**
```
Accessibility-Audit: OK – alle relevanten Bilder haben Alt-Texte oder sind als dekorativ markiert.
```

**Fehler:**
```
Accessibility-Audit fehlgeschlagen: 5 Bild(er) ohne Alt-Text gefunden.

Details:
┌─────┬─────────────────┬─────┐
│ UID │ Kontext         │ PID │
├─────┼─────────────────┼─────┤
│ 123 │ tt_content:1234 │ 1   │
└─────┴─────────────────┴─────┘
```










