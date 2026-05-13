<?php
require_once __DIR__ . '/../config/config.php';

class Riparti
{
    public static function all(?int $condominioId = null): array
    {
        global $pdo;
        $sql = 'SELECT r.*, c.nome AS condominio_nome, e.nome AS esercizio_nome,
                cs.nome AS categoria_nome, tm.nome AS tabella_nome
                FROM riparti r
                JOIN condomini c ON c.id = r.condominio_id
                JOIN esercizi e ON e.id = r.esercizio_id
                LEFT JOIN categorie_spesa cs ON cs.id = r.categoria_id
                LEFT JOIN tabelle_millesimali tm ON tm.id = r.tabella_personalizzata_id';
        $params = [];
        if ($condominioId) {
            $sql .= ' WHERE r.condominio_id = :cid';
            $params['cid'] = $condominioId;
        }
        $sql .= ' ORDER BY r.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT r.*, c.nome AS condominio_nome, e.nome AS esercizio_nome,
                               cs.nome AS categoria_nome, tm.nome AS tabella_nome
                               FROM riparti r
                               JOIN condomini c ON c.id = r.condominio_id
                               JOIN esercizi e ON e.id = r.esercizio_id
                               LEFT JOIN categorie_spesa cs ON cs.id = r.categoria_id
                               LEFT JOIN tabelle_millesimali tm ON tm.id = r.tabella_personalizzata_id
                               WHERE r.id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data)
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO riparti (condominio_id, esercizio_id, descrizione, tipo_millesimi, tabella_personalizzata_id, importo_totale, tipo_spesa, categoria_id, scala_filtro, stato, num_rate, note)
                               VALUES (:condominio_id, :esercizio_id, :descrizione, :tipo_millesimi, :tabella_personalizzata_id, :importo_totale, :tipo_spesa, :categoria_id, :scala_filtro, :stato, :num_rate, :note)');
        $ok = $stmt->execute([
            'condominio_id' => (int)$data['condominio_id'],
            'esercizio_id' => (int)$data['esercizio_id'],
            'descrizione' => trim($data['descrizione']),
            'tipo_millesimi' => $data['tipo_millesimi'] ?? 'proprieta',
            'tabella_personalizzata_id' => !empty($data['tabella_personalizzata_id']) ? (int)$data['tabella_personalizzata_id'] : null,
            'importo_totale' => (float)$data['importo_totale'],
            'tipo_spesa' => $data['tipo_spesa'] ?? 'ordinaria',
            'categoria_id' => !empty($data['categoria_id']) ? (int)$data['categoria_id'] : null,
            'scala_filtro' => !empty($data['scala_filtro']) ? trim($data['scala_filtro']) : null,
            'stato' => 'bozza',
            'num_rate' => max(1, (int)($data['num_rate'] ?? 1)),
            'note' => trim($data['note'] ?? '')
        ]);
        return $ok ? $pdo->lastInsertId() : false;
    }

    public static function update(int $id, array $data): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE riparti SET descrizione = :descrizione, tipo_millesimi = :tipo_millesimi,
                               tabella_personalizzata_id = :tabella_personalizzata_id,
                               importo_totale = :importo_totale, tipo_spesa = :tipo_spesa,
                               categoria_id = :categoria_id, scala_filtro = :scala_filtro,
                               num_rate = :num_rate, note = :note, updated_at = NOW() WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'descrizione' => trim($data['descrizione']),
            'tipo_millesimi' => $data['tipo_millesimi'] ?? 'proprieta',
            'tabella_personalizzata_id' => !empty($data['tabella_personalizzata_id']) ? (int)$data['tabella_personalizzata_id'] : null,
            'importo_totale' => (float)$data['importo_totale'],
            'tipo_spesa' => $data['tipo_spesa'] ?? 'ordinaria',
            'categoria_id' => !empty($data['categoria_id']) ? (int)$data['categoria_id'] : null,
            'scala_filtro' => !empty($data['scala_filtro']) ? trim($data['scala_filtro']) : null,
            'num_rate' => max(1, (int)($data['num_rate'] ?? 1)),
            'note' => trim($data['note'] ?? '')
        ]);
    }

    public static function delete(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM riparti WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function updateStato(int $id, string $stato): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE riparti SET stato = :stato, updated_at = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $id, 'stato' => $stato]);
    }

    /**
     * Calcola il riparto: per ogni unita del condominio (filtrata per scala se impostato),
     * determina la quota in base alla tabella millesimale scelta.
     * Supporta: esclusioni, tabelle personalizzate, spese individuali, scala filtro.
     */
    public static function calcola(int $ripartoId): bool
    {
        global $pdo;
        $riparto = self::find($ripartoId);
        if (!$riparto) return false;

        $importoTotale = (float)$riparto['importo_totale'];
        $condominioId = (int)$riparto['condominio_id'];

        // Get units filtered by scala if set
        $sqlUnita = "SELECT id, scala, piano, interno, millesimi_proprieta, millesimi_scale, millesimi_ascensore, millesimi_riscaldamento
                     FROM unita_immobiliari WHERE condominio_id = :cid AND status = 'active'";
        $paramsUnita = ['cid' => $condominioId];
        if (!empty($riparto['scala_filtro'])) {
            $sqlUnita .= ' AND scala = :scala';
            $paramsUnita['scala'] = $riparto['scala_filtro'];
        }
        $sqlUnita .= ' ORDER BY scala, piano, interno';
        $stmt = $pdo->prepare($sqlUnita);
        $stmt->execute($paramsUnita);
        $unita = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($unita)) return false;

        // Get millesimi values
        $millesimi = [];
        if ($riparto['tipo_millesimi'] === 'personalizzato' && !empty($riparto['tabella_personalizzata_id'])) {
            $stmtP = $pdo->prepare('SELECT unita_id, valore FROM millesimi_personalizzati WHERE tabella_id = :tid');
            $stmtP->execute(['tid' => (int)$riparto['tabella_personalizzata_id']]);
            foreach ($stmtP->fetchAll(PDO::FETCH_ASSOC) as $mp) {
                $millesimi[(int)$mp['unita_id']] = (float)$mp['valore'];
            }
        }

        $colonna = self::colonnaMillesimi($riparto['tipo_millesimi']);

        // Check for existing exclusions
        $existingExclusions = [];
        $stmtExcl = $pdo->prepare('SELECT unita_id FROM riparti_dettaglio WHERE riparto_id = :rid AND esclusa = 1');
        $stmtExcl->execute(['rid' => $ripartoId]);
        foreach ($stmtExcl->fetchAll(PDO::FETCH_ASSOC) as $ex) {
            $existingExclusions[(int)$ex['unita_id']] = true;
        }

        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare('DELETE FROM riparti_dettaglio WHERE riparto_id = :rid');
            $del->execute(['rid' => $ripartoId]);

            $ins = $pdo->prepare('INSERT INTO riparti_dettaglio (riparto_id, unita_id, millesimi, importo, esclusa) VALUES (:rid, :uid, :mill, :imp, :escl)');

            // For individual expenses, assign 100% to first unit (user picks unit in detail)
            if ($riparto['tipo_spesa'] === 'individuale') {
                foreach ($unita as $i => $u) {
                    $imp = ($i === 0) ? $importoTotale : 0;
                    $ins->execute(['rid' => $ripartoId, 'uid' => (int)$u['id'], 'mill' => 0, 'imp' => $imp, 'escl' => 0]);
                }
                self::updateStato($ripartoId, 'calcolato');
                $pdo->commit();
                return true;
            }

            // Collect millesimi per unit, respecting exclusions
            $unitaConMillesimi = [];
            $totaleMillesimi = 0;
            foreach ($unita as $u) {
                $uid = (int)$u['id'];
                $esclusa = isset($existingExclusions[$uid]) ? 1 : 0;
                if ($riparto['tipo_millesimi'] === 'personalizzato') {
                    $mill = $millesimi[$uid] ?? 0;
                } else {
                    $mill = (float)$u[$colonna];
                }
                $unitaConMillesimi[] = ['id' => $uid, 'millesimi' => $mill, 'esclusa' => $esclusa];
                if (!$esclusa) $totaleMillesimi += $mill;
            }

            $sommaImporti = 0;
            $nonEscluse = array_filter($unitaConMillesimi, fn($u) => !$u['esclusa']);
            $nonEscluseKeys = array_keys($nonEscluse);
            $lastKey = end($nonEscluseKeys);

            foreach ($unitaConMillesimi as $i => $u) {
                if ($u['esclusa']) {
                    $ins->execute(['rid' => $ripartoId, 'uid' => $u['id'], 'mill' => $u['millesimi'], 'imp' => 0, 'escl' => 1]);
                    continue;
                }
                if ($totaleMillesimi > 0) {
                    $quota = round($importoTotale * $u['millesimi'] / $totaleMillesimi, 2);
                } else {
                    $quota = round($importoTotale / count($nonEscluse), 2);
                }
                // Last non-excluded unit gets remainder for rounding
                if ($i === $lastKey) {
                    $quota = round($importoTotale - $sommaImporti, 2);
                }
                $sommaImporti += $quota;
                $ins->execute(['rid' => $ripartoId, 'uid' => $u['id'], 'mill' => $u['millesimi'], 'imp' => $quota, 'escl' => 0]);
            }

            self::updateStato($ripartoId, 'calcolato');
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public static function getDettaglio(int $ripartoId): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT rd.*, ui.scala, ui.piano, ui.interno, ui.descrizione AS unita_desc,
                               ui.millesimi_proprieta, ui.millesimi_scale, ui.millesimi_ascensore, ui.millesimi_riscaldamento
                               FROM riparti_dettaglio rd
                               JOIN unita_immobiliari ui ON ui.id = rd.unita_id
                               WHERE rd.riparto_id = :rid
                               ORDER BY ui.scala, ui.piano, ui.interno');
        $stmt->execute(['rid' => $ripartoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Toggle exclusion of a unit from riparto. Recalculates if already calculated.
     */
    public static function toggleEsclusione(int $ripartoId, int $unitaId): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT esclusa FROM riparti_dettaglio WHERE riparto_id = :rid AND unita_id = :uid');
        $stmt->execute(['rid' => $ripartoId, 'uid' => $unitaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        $newVal = $row['esclusa'] ? 0 : 1;
        $upd = $pdo->prepare('UPDATE riparti_dettaglio SET esclusa = :escl, importo = 0 WHERE riparto_id = :rid AND unita_id = :uid');
        return $upd->execute(['escl' => $newVal, 'rid' => $ripartoId, 'uid' => $unitaId]);
    }

    /**
     * Manually adjust (rettifica) the importo for a specific unit in the riparto.
     */
    public static function rettificaQuota(int $dettaglioId, float $nuovoImporto): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('UPDATE riparti_dettaglio SET importo_rettificato = :imp WHERE id = :id');
        return $stmt->execute(['imp' => $nuovoImporto, 'id' => $dettaglioId]);
    }

    /**
     * Get the effective importo for a dettaglio row (rettificato if set, else importo).
     */
    public static function importoEffettivo(array $dettaglio): float
    {
        if ($dettaglio['importo_rettificato'] !== null) {
            return (float)$dettaglio['importo_rettificato'];
        }
        return (float)$dettaglio['importo'];
    }

    /**
     * Genera le rate dal riparto calcolato, linking them back to riparto.
     */
    public static function generaRate(int $ripartoId, string $dataInizioScadenza): bool
    {
        global $pdo;
        $riparto = self::find($ripartoId);
        if (!$riparto || !in_array($riparto['stato'], ['calcolato', 'approvato'])) return false;

        $dettaglio = self::getDettaglio($ripartoId);
        if (empty($dettaglio)) return false;

        $numRate = max(1, (int)$riparto['num_rate']);

        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare('INSERT INTO rate (esercizio_id, condominio_id, unita_id, descrizione, importo, scadenza, stato, riparto_id)
                                  VALUES (:eid, :cid, :uid, :desc, :imp, :scad, :stato, :rid)');

            foreach ($dettaglio as $d) {
                if ($d['esclusa']) continue;
                $importoUnita = self::importoEffettivo($d);
                if ($importoUnita <= 0) continue;

                $importoRata = round($importoUnita / $numRate, 2);
                $somma = 0;

                for ($r = 0; $r < $numRate; $r++) {
                    $scadenza = date('Y-m-d', strtotime($dataInizioScadenza . " +{$r} months"));
                    $imp = ($r === $numRate - 1) ? round($importoUnita - $somma, 2) : $importoRata;
                    $somma += $imp;

                    $unitaLabel = trim(($d['scala'] ?? '') . ' ' . ($d['piano'] ?? '') . ' ' . ($d['interno'] ?? ''));
                    $desc = $riparto['descrizione'] . ' - ' . ($unitaLabel ?: 'Unita ' . $d['unita_id']);
                    if ($numRate > 1) {
                        $desc .= ' (rata ' . ($r + 1) . '/' . $numRate . ')';
                    }

                    $ins->execute([
                        'eid' => (int)$riparto['esercizio_id'],
                        'cid' => (int)$riparto['condominio_id'],
                        'uid' => (int)$d['unita_id'],
                        'desc' => $desc,
                        'imp' => $imp,
                        'scad' => $scadenza,
                        'stato' => 'da_pagare',
                        'rid' => $ripartoId
                    ]);
                }
            }

            self::updateStato($ripartoId, 'rate_generate');
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }

    /**
     * Riepilogo millesimi per condominio.
     */
    public static function riepilogoMillesimi(int $condominioId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT
            SUM(millesimi_proprieta) AS tot_proprieta,
            SUM(millesimi_scale) AS tot_scale,
            SUM(millesimi_ascensore) AS tot_ascensore,
            SUM(millesimi_riscaldamento) AS tot_riscaldamento,
            COUNT(*) AS num_unita
            FROM unita_immobiliari WHERE condominio_id = :cid AND status = 'active'");
        $stmt->execute(['cid' => $condominioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get distinct scala values for a condominio.
     */
    public static function scaleCondominio(int $condominioId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT DISTINCT scala FROM unita_immobiliari WHERE condominio_id = :cid AND status = 'active' AND scala IS NOT NULL AND scala != '' ORDER BY scala");
        $stmt->execute(['cid' => $condominioId]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'scala');
    }

    /**
     * Rate generate da un riparto.
     */
    public static function rateGenerate(int $ripartoId): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT r.*, ui.scala, ui.piano, ui.interno FROM rate r JOIN unita_immobiliari ui ON ui.id = r.unita_id WHERE r.riparto_id = :rid ORDER BY ui.scala, ui.piano, ui.interno, r.scadenza');
        $stmt->execute(['rid' => $ripartoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Tabelle millesimali personalizzate ---

    public static function tabelleMillesimali(int $condominioId): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM tabelle_millesimali WHERE condominio_id = :cid ORDER BY nome');
        $stmt->execute(['cid' => $condominioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findTabella(int $id): ?array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT tm.*, c.nome AS condominio_nome FROM tabelle_millesimali tm JOIN condomini c ON c.id = tm.condominio_id WHERE tm.id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function createTabella(int $condominioId, string $nome, string $descrizione = ''): int
    {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO tabelle_millesimali (condominio_id, nome, descrizione) VALUES (:cid, :nome, :desc)');
        $stmt->execute(['cid' => $condominioId, 'nome' => trim($nome), 'desc' => trim($descrizione)]);
        return (int)$pdo->lastInsertId();
    }

    public static function deleteTabella(int $id): bool
    {
        global $pdo;
        $stmt = $pdo->prepare('DELETE FROM tabelle_millesimali WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public static function getMillesimiPersonalizzati(int $tabellaId): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT mp.*, ui.scala, ui.piano, ui.interno, ui.descrizione AS unita_desc
                               FROM millesimi_personalizzati mp
                               JOIN unita_immobiliari ui ON ui.id = mp.unita_id
                               WHERE mp.tabella_id = :tid
                               ORDER BY ui.scala, ui.piano, ui.interno');
        $stmt->execute(['tid' => $tabellaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function salvaMillesimiPersonalizzati(int $tabellaId, array $unitaIds, array $valori): bool
    {
        global $pdo;
        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare('DELETE FROM millesimi_personalizzati WHERE tabella_id = :tid');
            $del->execute(['tid' => $tabellaId]);
            $ins = $pdo->prepare('INSERT INTO millesimi_personalizzati (tabella_id, unita_id, valore) VALUES (:tid, :uid, :val)');
            foreach ($unitaIds as $i => $uid) {
                $val = (float)($valori[$i] ?? 0);
                if ($val > 0) {
                    $ins->execute(['tid' => $tabellaId, 'uid' => (int)$uid, 'val' => $val]);
                }
            }
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public static function totaleMillesimiTabella(int $tabellaId): float
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(valore), 0) FROM millesimi_personalizzati WHERE tabella_id = :tid');
        $stmt->execute(['tid' => $tabellaId]);
        return (float)$stmt->fetchColumn();
    }

    private static function colonnaMillesimi(string $tipo): string
    {
        $map = [
            'proprieta' => 'millesimi_proprieta',
            'scale' => 'millesimi_scale',
            'ascensore' => 'millesimi_ascensore',
            'riscaldamento' => 'millesimi_riscaldamento',
            'personalizzato' => 'millesimi_proprieta',
        ];
        return $map[$tipo] ?? 'millesimi_proprieta';
    }
}
