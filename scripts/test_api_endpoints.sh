#!/bin/bash

# ==================================================================
# FAHN-CORE API Endpoint Test Script
# ==================================================================
# Dieses Script testet alle API-Endpunkte und gibt eine detaillierte
# Auswertung zurück.
# ==================================================================

set -e

BASE_URL="https://fahn-core-typo3.ddev.site"
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "=================================================================="
echo "FAHN-CORE API Endpoint Test"
echo "=================================================================="
echo ""

# Funktion: Test durchführen
test_endpoint() {
    local name=$1
    local url=$2
    local expected_type=$3
    
    echo -n "Testing: $name... "
    
    response=$(curl -s -w "\n%{http_code}" "$url" 2>/dev/null)
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    if [ "$http_code" = "200" ]; then
        # Prüfe ob JSON
        if echo "$body" | grep -q "json\|authenticated\|success\|items\|error"; then
            echo -e "${GREEN}✅ OK${NC} (HTTP $http_code, JSON detected)"
            echo "   Response preview: $(echo "$body" | head -c 80)..."
            return 0
        elif echo "$body" | grep -q "<!DOCTYPE html>"; then
            echo -e "${RED}❌ FAIL${NC} (HTTP $http_code, HTML instead of JSON)"
            echo "   Response: $(echo "$body" | head -c 100)..."
            return 1
        else
            echo -e "${YELLOW}⚠️  WARNING${NC} (HTTP $http_code, unknown format)"
            echo "   Response: $(echo "$body" | head -c 80)..."
            return 1
        fi
    else
        echo -e "${RED}❌ FAIL${NC} (HTTP $http_code)"
        if [ "$http_code" = "404" ]; then
            echo "   Error: Page Not Found - TypoScript may not be loaded"
        fi
        return 1
    fi
}

# Cache leeren
echo "Step 1: Clearing TYPO3 cache..."
ddev exec vendor/bin/typo3 cache:flush > /dev/null 2>&1
echo -e "${GREEN}✅ Cache cleared${NC}"
echo ""

# Tests durchführen
echo "Step 2: Testing API endpoints..."
echo ""

PASSED=0
FAILED=0

# Test 1: Session Endpoint
if test_endpoint "Session Status" \
    "${BASE_URL}/?tx_fahncore_login%5Baction%5D=session" \
    "json"; then
    ((PASSED++))
else
    ((FAILED++))
fi
echo ""

# Test 2: Fahndungen List
if test_endpoint "Fahndungen List" \
    "${BASE_URL}/?tx_fahncorefahndung_api%5Baction%5D=list&tx_fahncorefahndung_api%5Bpage%5D=1" \
    "json"; then
    ((PASSED++))
else
    ((FAILED++))
fi
echo ""

# Test 3: Health Check (typeNum)
if test_endpoint "Health Check (typeNum 8999)" \
    "${BASE_URL}/?type=8999" \
    "json"; then
    ((PASSED++))
else
    ((FAILED++))
fi
echo ""

# Zusammenfassung
echo "=================================================================="
echo "Test Summary"
echo "=================================================================="
echo -e "Passed: ${GREEN}$PASSED${NC}"
echo -e "Failed: ${RED}$FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ All tests passed!${NC}"
    echo ""
    echo "Next steps:"
    echo "  1. Verify in TYPO3 Backend: Web > Template > Template Analyzer"
    echo "  2. Search for 'tx_fahncore' - should find plugin configurations"
    echo "  3. Check that 'features.skipDefaultArguments = 1' is set"
    exit 0
else
    echo -e "${RED}❌ Some tests failed!${NC}"
    echo ""
    echo "Troubleshooting:"
    echo "  1. Check Extension Manager: Are fahn_core and fahn_core_fahndung activated?"
    echo "  2. Check Template: Are TypoScript imports present?"
    echo "  3. Check Template Analyzer: Search for 'tx_fahncore'"
    echo "  4. See detailed checklist: docs/BACKEND-VERIFIKATIONS-CHECKLISTE.md"
    exit 1
fi


