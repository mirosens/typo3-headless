# PHASE A.10 – Observability (Logging, Healthcheck, Tracing)

**Stand:** 29.11.2025 – Projekt: FAHN-CORE TYPO3 Headless  
**Status:** 🟢 Ready for Production Implementation

## Übersicht

Diese Dokumentation beschreibt die Implementierung der Observability-Features für FAHN-CORE:

- **Logging**: Strukturierte, containerfreundliche Logs nach stdout/stderr (JSON-/logfmt-fähig)
- **Health Checks**: Stabile Health-Endpoints für Liveness/Readiness (HTTP 200/503) für Docker/Kubernetes
- **Tracing**: Durchgängige X-Request-ID vom Next.js-Frontend bis in jedes TYPO3-Logevent

## Implementierte Komponenten

### 1. Logging-Konfiguration

**Datei:** `config/system/observability.php`

- Logs werden nach stdout (INFO+) und stderr (ERROR+) geschrieben
- Keine lokalen Logfiles im Container
- Format ist logfmt-ähnlich und kann in zentralen Log-Pipelines (Loki, ELK) geparst werden
- Log-Level konfigurierbar über Environment-Variable `TYPO3_LOG_LEVEL` (Default: INFO)

**Verfügbare Log-Level:**
- `DEBUG`
- `INFO` (Default)
- `WARNING`
- `ERROR`

**Umgebungsvariable:**
```bash
TYPO3_LOG_LEVEL=INFO
```

### 2. Health Check Endpoints

**Middleware:** `packages/fahn_core/Classes/Middleware/HealthCheckMiddleware.php`

**Endpoints:**
- `/health/live` - Liveness Probe (PHP-Prozess lebt)
  - Antwort: HTTP 200 mit `{ "status": "alive", "timestamp": "..." }`
  
- `/health/ready` - Readiness Probe (DB & Redis erreichbar)
  - Antwort: HTTP 200 bei gesunden Dependencies
  - Antwort: HTTP 503 bei fehlgeschlagenen Checks
  - Enthält Details zu jedem Check: `{ "healthy": true, "checks": { "database": "ok", "redis": "ok" }, "timestamp": "..." }`

**Sicherheit:**
- Optional: Token-basierte Absicherung über Environment-Variable `HEALTH_CHECK_TOKEN`
- Header: `X-Health-Token` muss gesetzt werden, wenn `HEALTH_CHECK_TOKEN` definiert ist

**Umgebungsvariable:**
```bash
HEALTH_CHECK_TOKEN=your-secret-token-here
```

### 3. Request ID Tracing

**Processor:** `packages/fahn_core/Classes/Log/Processor/RequestIdProcessor.php`

- Liest `X-Request-ID` Header aus dem Request
- Fügt `request_id` Feld zu jedem LogRecord hinzu
- Ermöglicht end-to-end Tracing vom Next.js-Frontend bis in TYPO3-Logs

**Verwendung im Next.js Frontend:**
```typescript
// Beispiel: UUID v4 generieren und als Header senden
import { v4 as uuidv4 } from 'uuid';

const requestId = uuidv4();
fetch('https://typo3-api.example.com/api/page', {
  headers: {
    'X-Request-ID': requestId
  }
});
```

## Docker Healthcheck Konfiguration

### Docker Compose Beispiel

```yaml
services:
  typo3:
    image: fahn-core/typo3:latest
    # ... weitere Konfiguration ...
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/health/ready"]
      interval: 30s
      timeout: 5s
      retries: 3
      start_period: 20s  # gibt TYPO3 Zeit zum Booten
    environment:
      - HEALTH_CHECK_TOKEN=${HEALTH_CHECK_TOKEN}
      - TYPO3_LOG_LEVEL=${TYPO3_LOG_LEVEL:-INFO}
```

### Kubernetes Liveness/Readiness Probes

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: typo3
spec:
  template:
    spec:
      containers:
      - name: typo3
        image: fahn-core/typo3:latest
        livenessProbe:
          httpGet:
            path: /health/live
            port: 80
          initialDelaySeconds: 20
          periodSeconds: 30
          timeoutSeconds: 5
          failureThreshold: 3
        readinessProbe:
          httpGet:
            path: /health/ready
            port: 80
            httpHeaders:
            - name: X-Health-Token
              value: "your-secret-token"
          initialDelaySeconds: 10
          periodSeconds: 10
          timeoutSeconds: 5
          failureThreshold: 3
        env:
        - name: HEALTH_CHECK_TOKEN
          valueFrom:
            secretKeyRef:
              name: typo3-secrets
              key: health-check-token
        - name: TYPO3_LOG_LEVEL
          value: "INFO"
```

## Netzwerk-Sicherheit

### Nginx/Ingress Konfiguration

Health-Endpoints sollten nur intern erreichbar sein:

```nginx
# Nginx Beispiel
location ~ ^/health/ {
    # Nur aus dem Cluster erlauben
    allow 10.0.0.0/8;
    allow 172.16.0.0/12;
    allow 192.168.0.0/16;
    deny all;
    
    proxy_pass http://typo3-backend;
    proxy_set_header X-Health-Token $http_x_health_token;
}
```

### Kubernetes Ingress Beispiel

```yaml
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: typo3-ingress
  annotations:
    nginx.ingress.kubernetes.io/server-snippet: |
      location ~ ^/health/ {
        allow 10.0.0.0/8;
        allow 172.16.0.0/12;
        allow 192.168.0.0/16;
        deny all;
      }
spec:
  rules:
  - host: typo3.example.com
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: typo3
            port:
              number: 80
```

## Logging in Produktion

### Log-Ausgabe prüfen

```bash
# Docker
docker logs <typo3-container-name>

# Kubernetes
kubectl logs <pod-name> -c typo3

# Mit Filter
docker logs <container> 2>&1 | grep "request_id"
```

### Log-Format Beispiel

```
2025-11-29T10:15:30+00:00 INFO TYPO3.CMS.Core.Http.Application [request_id="7b41c2c2-9d3f-4d1f-8fa3-9180fd1ef001"] Request processed successfully
```

### Integration mit Log-Aggregation

Die Logs sind im logfmt-ähnlichen Format und können direkt von folgenden Tools geparst werden:

- **Grafana Loki**: Automatisches Parsing von stdout/stderr
- **ELK Stack**: Logstash kann das Format parsen
- **Datadog**: Unterstützt automatisches Parsing von Container-Logs

## Testing

### Health Check Endpoints testen

```bash
# Liveness Probe
curl http://localhost/health/live

# Readiness Probe (ohne Token)
curl http://localhost/health/ready

# Readiness Probe (mit Token)
curl -H "X-Health-Token: your-secret-token" http://localhost/health/ready
```

### Logging testen

1. TYPO3-Backend aufrufen
2. Eine Log-Meldung erzeugen (z.B. via Test-Command oder bewusst fehlerhafte Konfiguration)
3. Container-Logs prüfen:

```bash
# DDEV
ddev logs

# Docker
docker logs <typo3-container-name>

# Erwartung:
# - INFO-/DEBUG-Meldungen erscheinen auf stdout
# - ERROR/CRITICAL-Meldungen erscheinen auf stderr
# - Prozessor-Daten (URL, Speicher, File/Line) sind in der Logzeile sichtbar
# - request_id ist in jedem Log-Eintrag enthalten (wenn X-Request-ID Header gesetzt)
```

## Definition of Done (DoD)

### Logging
- [x] TYPO3 schreibt keine produktiven Logs mehr in lokale Dateien im Container
- [x] FileWriter ist so konfiguriert, dass INFO+ nach stdout, ERROR+ nach stderr geht
- [x] Kontextdaten (URL, Speicher, Dateiname/Zeile) sind in Logs enthalten
- [x] RequestIdProcessor ist aktiv und schreibt X-Request-ID in jedes Logevent
- [x] Logs sind über `docker logs` bzw. `kubectl logs` direkt lesbar

### Healthchecks
- [x] `/health/live` liefert immer HTTP 200 mit `{ status: "alive" }`
- [x] `/health/ready` prüft mindestens DB und Redis und liefert bei Problemen HTTP 503
- [x] HealthCheckMiddleware ist registriert und arbeitet vor komplexen Frontend-/Routing-Schichten
- [x] Docker-Healthcheck ist dokumentiert und kann in docker-compose.yml konfiguriert werden

### Security & Tracing
- [x] Health-Endpoints können über Token abgesichert werden
- [x] X-Request-ID-Konzept ist vorbereitet und über RequestIdProcessor in den Logs sichtbar

## Weitere Schritte

1. **Produktions-Deployment**: Docker Healthcheck in docker-compose.yml oder Kubernetes-Manifesten konfigurieren
2. **Log-Aggregation**: Integration mit Grafana Loki, ELK Stack oder ähnlichen Tools
3. **Monitoring**: Alerts basierend auf Health-Check-Status konfigurieren
4. **Next.js Integration**: X-Request-ID Header in allen API-Requests setzen






