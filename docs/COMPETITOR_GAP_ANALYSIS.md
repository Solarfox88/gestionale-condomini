# Analisi Gap Competitiva — Gestionale Condomini

**Data**: Maggio 2026
**Versione analizzata**: v2.0.0 (post EPIC #13)
**Competitor confrontati**: Domustudio (Danea), Condexo, KondoManager (open source), Safenet Condominio, Gecos

---

## 1. Panoramica Competitor

| Software | Tipo | Prezzo | Target | Stack |
|----------|------|--------|--------|-------|
| **Domustudio** (Danea) | SaaS/Cloud | €228-468/anno | Studi professionali | Windows/Cloud proprietario |
| **Condexo** | SaaS/PropTech | Su richiesta | Grandi studi, marketplace | Cloud proprietario |
| **KondoManager** | Open Source | Gratuito (AGPL-3) | Piccoli studi, self-hosted | Laravel + MySQL |
| **Safenet** | Desktop/Cloud | ~€200-400/anno | Studi medi | Windows |
| **Il nostro** | Open Source | Gratuito | Self-hosted cPanel | PHP vanilla + MySQL |

---

## 2. Matrice Funzionalita: Nostro vs Competitor

### Legenda
- ✅ Implementato e funzionante
- ⚠️ Implementato parzialmente
- ❌ Non implementato
- N/A Non applicabile

| Funzionalita | Nostro | Domustudio | Condexo | KondoManager |
|---|:---:|:---:|:---:|:---:|
| **ANAGRAFICHE** | | | | |
| Condomini CRUD | ✅ | ✅ | ✅ | ✅ |
| Unita immobiliari | ✅ | ✅ | ✅ | ✅ |
| Persone/anagrafiche | ✅ | ✅ | ✅ | ✅ |
| Associazioni unita-persone | ✅ | ✅ | ✅ | ✅ |
| Supercondomini | ❌ | ✅ | ✅ | ❌ |
| Catasto/dati catastali | ❌ | ✅ | ⚠️ | ❌ |
| **CONTABILITA** | | | | |
| Esercizi contabili | ✅ | ✅ | ✅ | ✅ |
| Movimenti/prima nota | ✅ | ✅ | ✅ | ✅ |
| Categorie spesa | ✅ | ✅ | ✅ | ✅ |
| Preventivi | ✅ | ✅ | ✅ | ✅ |
| Consuntivi + confronto | ✅ | ✅ | ✅ | ✅ |
| Conguagli | ✅ | ✅ | ✅ | ⚠️ |
| Chiusura esercizio con blocco | ✅ | ✅ | ✅ | ⚠️ |
| Piano dei conti personalizzabile | ❌ | ✅ | ✅ | ❌ |
| Partita doppia | ❌ | ✅ | ⚠️ | ❌ |
| Conto corrente/riconciliazione bancaria | ❌ | ✅ | ✅ | ❌ |
| **MILLESIMI E RIPARTI** | | | | |
| Tabelle millesimali standard | ✅ | ✅ | ✅ | ✅ |
| Tabelle custom | ✅ | ✅ | ✅ | ❌ |
| Esclusione unita da riparto | ✅ | ✅ | ✅ | ❌ |
| Spese individuali | ✅ | ✅ | ✅ | ⚠️ |
| Rettifica manuale quote | ✅ | ✅ | ✅ | ❌ |
| Arrotondamenti | ✅ | ✅ | ✅ | ✅ |
| **RATE E PAGAMENTI** | | | | |
| Rate manuali + da riparto | ✅ | ✅ | ✅ | ✅ |
| Pagamenti parziali | ✅ | ✅ | ✅ | ✅ |
| Morosita + solleciti | ✅ | ✅ | ✅ | ⚠️ |
| MAV/bollettini bancari | ❌ | ✅ | ✅ | ❌ |
| Pagamenti online (PagoPA/Stripe) | ❌ | ⚠️ | ✅ | ❌ |
| SDD/addebito diretto | ❌ | ✅ | ✅ | ❌ |
| **DOCUMENTI** | | | | |
| Upload/download protetto | ✅ | ✅ | ✅ | ✅ |
| Categorie documento | ✅ | ✅ | ✅ | ✅ |
| Visibilita per ruolo | ✅ | ✅ | ✅ | ✅ |
| Firma digitale | ❌ | ✅ | ⚠️ | ❌ |
| OCR fatture | ❌ | ❌ | ✅ | ❌ |
| **TICKET/MANUTENZIONI** | | | | |
| Apertura ticket condomino | ✅ | ⚠️ | ✅ | ✅ |
| Stati workflow (7 stati) | ✅ | ✅ | ✅ | ✅ |
| Assegnazione fornitore | ✅ | ✅ | ✅ | ✅ |
| Messaggi/note interne | ✅ | ✅ | ✅ | ✅ |
| Allegati foto | ✅ | ✅ | ✅ | ✅ |
| Marketplace fornitori | ❌ | ❌ | ✅ | ❌ |
| **ASSEMBLEE** | | | | |
| Gestione assemblee | ✅ | ✅ | ✅ | ✅ |
| Presenze e deleghe | ✅ | ✅ | ✅ | ✅ |
| Calcolo quorum | ⚠️ | ✅ | ✅ | ⚠️ |
| Votazioni online | ❌ | ⚠️ | ✅ | ❌ |
| Verbale automatico | ⚠️ | ✅ | ✅ | ❌ |
| **COMUNICAZIONI** | | | | |
| Comunicazioni massive | ✅ | ✅ | ✅ | ✅ |
| Template predefiniti | ✅ | ✅ | ✅ | ⚠️ |
| Email SMTP | ✅ | ✅ | ✅ | ✅ |
| Notifiche push/app | ❌ | ✅ | ✅ | ❌ |
| Bacheca condominio | ❌ | ✅ | ✅ | ✅ |
| **STAMPE E REPORT** | | | | |
| Stampe HTML print-ready | ✅ | ✅ | ✅ | ✅ |
| Export CSV | ✅ | ✅ | ✅ | ✅ |
| Export Excel nativo | ❌ | ✅ | ✅ | ❌ |
| PDF nativo (server-side) | ❌ | ✅ | ✅ | ✅ (DomPDF) |
| Report grafici/dashboard | ❌ | ✅ | ✅ | ⚠️ |
| **FISCALE** | | | | |
| CU (Certificazione Unica) | ❌ | ✅ | ✅ | ❌ |
| Modello 770 | ❌ | ✅ | ✅ | ❌ |
| Quadro AC | ❌ | ✅ | ⚠️ | ❌ |
| F24 | ❌ | ✅ | ✅ | ❌ |
| Detrazioni fiscali/Superbonus | ❌ | ✅ | ✅ | ❌ |
| **AREA CONDOMINO** | | | | |
| Dashboard personale | ✅ | ✅ | ✅ | ✅ |
| Visualizza rate/pagamenti | ✅ | ✅ | ✅ | ✅ |
| Documenti personali | ✅ | ✅ | ✅ | ✅ |
| Ticket da area riservata | ✅ | ✅ | ✅ | ✅ |
| Assemblee e verbali | ✅ | ✅ | ✅ | ✅ |
| Profilo + cambio password | ✅ | ✅ | ✅ | ✅ |
| App mobile nativa | ❌ | ✅ | ✅ | ❌ |
| PWA/responsive | ⚠️ | ✅ | ✅ | ✅ |
| **SICUREZZA** | | | | |
| CSRF + prepared statements | ✅ | ✅ | ✅ | ✅ |
| Audit log | ✅ | ⚠️ | ✅ | ❌ |
| Soft delete | ✅ | ✅ | ✅ | ❌ |
| Blocco esercizio chiuso | ✅ | ✅ | ✅ | ⚠️ |
| 2FA | ❌ | ❌ | ✅ | ❌ |
| **MULTI-TENANT / SAAS** | | | | |
| Multi-studio | ✅ | ✅ | ✅ | ❌ |
| Ruoli granulari (9 ruoli) | ✅ | ⚠️ | ✅ | ⚠️ |
| Permessi per modulo | ✅ | ⚠️ | ✅ | ⚠️ |
| Piani/limiti SaaS | ✅ | ✅ | ✅ | ❌ |
| Branding studio | ⚠️ | ✅ | ✅ | ❌ |
| **IMPORT/EXPORT** | | | | |
| Import CSV | ✅ | ✅ | ✅ | ⚠️ |
| Export CSV | ✅ | ✅ | ✅ | ✅ |
| Backup SQL + storage | ✅ | ✅ | ⚠️ | ⚠️ |
| Import da altri gestionali | ❌ | ✅ (50+ sw) | ⚠️ | ❌ |

---

## 3. Gap Critici (P0 — Bloccanti per uso professionale)

### 3.1 Adempimenti fiscali (CU, 770, F24, Quadro AC)
**Stato**: ❌ Non implementato
**Impatto**: Un amministratore professionista DEVE produrre CU per i fornitori, presentare il 770, gestire F24 e quadro AC. Senza queste funzionalita, il gestionale e inutilizzabile per uno studio professionale.
**Competitor**: Domustudio e Condexo lo offrono completo.
**Sforzo stimato**: Alto (2-3 settimane). Richiede conoscenza normativa specifica.
**Raccomandazione**: Implementare almeno CU e quadro AC nella prossima release. Per F24/770 valutare integrazione con intermediari (Fisconline/Entratel).

### 3.2 Riconciliazione bancaria
**Stato**: ❌ Non implementato
**Impatto**: L'amministratore deve confrontare manualmente i movimenti contabili con l'estratto conto bancario. Rischio errori, perdita di tempo.
**Competitor**: Domustudio e Condexo offrono import estratto conto + riconciliazione automatica.
**Sforzo stimato**: Medio (1 settimana). Import CSV estratto conto → matching con movimenti.
**Raccomandazione**: Aggiungere import CSV estratto conto e matching semi-automatico.

### 3.3 PDF server-side
**Stato**: ❌ Non implementato (solo HTML print-ready)
**Impatto**: Le stampe HTML funzionano via browser, ma non permettono invio automatico PDF via email o generazione batch. Limita comunicazioni e solleciti automatici.
**Competitor**: Tutti i competitor generano PDF server-side.
**Sforzo stimato**: Basso-medio. Opzioni: TCPDF (singolo file PHP, no Composer), mPDF, o wkhtmltopdf.
**Raccomandazione**: Integrare TCPDF come singola libreria PHP (compatibile SupportHost) per generazione PDF da template HTML esistenti.

---

## 4. Gap Importanti (P1 — Necessari per competitivita)

### 4.1 MAV/Bollettini bancari
**Stato**: ❌
**Impatto**: I condòmini si aspettano bollettini precompilati per pagare le rate. Standard nel settore.
**Sforzo**: Medio. Generazione PDF bollettini con dati rata.

### 4.2 Pagamenti online
**Stato**: ❌
**Impatto**: Condexo offre pagamenti online integrati. Trend in crescita nel settore.
**Sforzo**: Medio-alto. Integrazione Stripe/PagoPA.

### 4.3 Dashboard analitica con grafici
**Stato**: ❌
**Impatto**: La dashboard attuale e testuale. I competitor mostrano grafici a torta/barre per spese, morosita, trend.
**Sforzo**: Basso. Chart.js (singolo file JS, zero dipendenze server).
**Raccomandazione**: Aggiungere Chart.js alla dashboard per: distribuzione spese per categoria, trend morosita, confronto preventivo/consuntivo.

### 4.4 Votazioni online assemblee
**Stato**: ❌
**Impatto**: Post-COVID, le assemblee online/ibride sono diffuse. Condexo offre votazioni online complete.
**Sforzo**: Medio. WebSocket o polling per voto in tempo reale.

### 4.5 App mobile / PWA
**Stato**: ⚠️ (responsive ma non PWA)
**Impatto**: Condexo ha app nativa. I condòmini si aspettano notifiche push.
**Sforzo**: Basso per PWA (manifest.json + service worker). Alto per app nativa.
**Raccomandazione**: Implementare PWA come primo step — installabile da browser, notifiche push, offline cache.

---

## 5. Gap Minori (P2 — Nice-to-have)

| Gap | Sforzo | Note |
|-----|--------|------|
| Supercondomini | Medio | Gerarchia condomini padre-figlio |
| Dati catastali | Basso | Campi aggiuntivi su unita |
| Firma digitale documenti | Alto | Integrazione con provider firma (es. Aruba) |
| OCR fatture | Alto | Integrazione con servizi OCR |
| Marketplace fornitori | Alto | Piattaforma a se stante |
| Import da 50+ gestionali | Alto | Parser specifici per ogni formato |
| Notifiche push native | Medio | Firebase Cloud Messaging |
| Partita doppia | Alto | Ristrutturazione contabilita |
| Export Excel nativo | Basso | PhpSpreadsheet o CSV con BOM per Excel |
| 2FA/TOTP | Basso | Libreria TOTP PHP |

---

## 6. Vantaggi Competitivi Unici

Il nostro gestionale ha vantaggi che **nessun competitor offre contemporaneamente**:

| Vantaggio | Domustudio | Condexo | KondoManager | Nostro |
|---|:---:|:---:|:---:|:---:|
| **Zero dipendenze** (no Composer) | ❌ | ❌ | ❌ (Laravel) | ✅ |
| **Installabile via FTP** su qualsiasi hosting | ❌ | ❌ | ❌ (richiede CLI) | ✅ |
| **Costo zero** + nessun abbonamento | ❌ | ❌ | ✅ | ✅ |
| **Nessun vendor lock-in** | ❌ | ❌ | ✅ | ✅ |
| **Compatibile hosting condiviso cPanel** | ❌ | ❌ | ⚠️ | ✅ |
| **Privacy totale** (dati su proprio server) | ❌ | ❌ | ✅ | ✅ |
| **Personalizzazione illimitata** | ❌ | ❌ | ✅ (complesso) | ✅ (semplice) |
| **Multi-tenant predisposto** | ⚠️ | ✅ | ❌ | ✅ |
| **Audit log completo** | ⚠️ | ✅ | ❌ | ✅ |
| **Soft delete** su dati contabili | ✅ | ✅ | ❌ | ✅ |

---

## 7. Roadmap Prioritizzata per Competitivita

### Fase Immediata (v2.1 — 2 settimane)
1. **PDF server-side** con TCPDF — sblocca invio documenti via email
2. **Dashboard con grafici** (Chart.js) — impatto visivo immediato
3. **PWA manifest** — installabilita da browser mobile
4. **Export Excel** — PhpSpreadsheet o CSV con BOM

### Fase Breve (v2.2 — 4 settimane)
5. **Riconciliazione bancaria** — import CSV estratto conto
6. **CU/Certificazione Unica** — primo adempimento fiscale
7. **MAV/Bollettini** — generazione PDF bollettini rata
8. **Bacheca condominio** — area comunicazioni condivisa

### Fase Media (v3.0 — 8 settimane)
9. **Modello 770 + F24** — adempimenti fiscali completi
10. **Votazioni online assemblee** — tempo reale con calcolo quorum
11. **Pagamenti online** (Stripe) — portale pagamento rate
12. **Notifiche push** — Firebase + service worker

### Fase Lunga (v4.0)
13. Supercondomini
14. Firma digitale
15. OCR fatture
16. App mobile nativa (React Native o Flutter)

---

## 8. Posizionamento di Mercato Consigliato

### Target primario: Amministratori singoli e piccoli studi (1-3 persone)
- Budget limitato per software
- Gestiscono 5-30 condomini
- Preferiscono controllo diretto sui dati
- Hosting gia esistente su SupportHost/Aruba/Register.it

### Differenziazione chiave
> "L'unico gestionale condominiale professionale che puoi installare sul tuo hosting in 5 minuti, senza abbonamenti, senza dipendenze, con i tuoi dati sul tuo server."

### Strategia di monetizzazione suggerita
1. **Core gratuito** (open source, self-hosted)
2. **Servizio di installazione** a pagamento (€99-199)
3. **Hosting gestito** con backup e aggiornamenti (€9.90/mese)
4. **Moduli premium** opzionali (adempimenti fiscali, firma digitale)
5. **Supporto prioritario** (€49/mese)

---

## 9. Conclusione

**Score complessivo post-EPIC #13: 7.8/10** (era 7.4 pre-EPIC #13)

Il gestionale copre il **75% delle funzionalita** richieste da un amministratore professionista. I gap principali sono negli adempimenti fiscali (CU, 770, F24) e nella riconciliazione bancaria — funzionalita specifiche del mercato italiano che richiedono conoscenza normativa.

Con l'implementazione di PDF server-side + CU + riconciliazione bancaria, il punteggio salirebbe a **8.5/10**, sufficiente per competere nel segmento "piccoli studi" con un vantaggio di prezzo e indipendenza imbattibile.
