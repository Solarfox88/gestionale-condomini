<?php

class EmailService
{
    public static function getConfig(): array
    {
        global $pdo;
        $defaults = [
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_user' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'smtp_from_email' => '',
            'smtp_from_name' => 'Gestionale Condomini',
            'smtp_attivo' => '0',
        ];
        try {
            $stmt = $pdo->query("SELECT chiave, valore FROM impostazioni WHERE chiave LIKE 'smtp_%'");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $defaults[$row['chiave']] = $row['valore'];
            }
        } catch (Exception $e) {
            // table may not exist yet
        }
        return $defaults;
    }

    public static function saveConfig(array $data): void
    {
        global $pdo;
        $keys = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_password', 'smtp_encryption', 'smtp_from_email', 'smtp_from_name', 'smtp_attivo'];
        $stmt = $pdo->prepare('INSERT INTO impostazioni (chiave, valore) VALUES (:k, :v) ON DUPLICATE KEY UPDATE valore=:v2, updated_at=NOW()');
        foreach ($keys as $k) {
            if (isset($data[$k])) {
                $stmt->execute(['k' => $k, 'v' => $data[$k], 'v2' => $data[$k]]);
            }
        }
    }

    public static function send(string $to, string $subject, string $body): mixed
    {
        $config = self::getConfig();
        if (empty($config['smtp_attivo']) || $config['smtp_attivo'] === '0') {
            return self::sendViaMailFunction($to, $subject, $body, $config);
        }
        return self::sendViaSMTP($to, $subject, $body, $config);
    }

    private static function sendViaMailFunction(string $to, string $subject, string $body, array $config): mixed
    {
        $fromEmail = $config['smtp_from_email'] ?: 'noreply@gestionale.local';
        $fromName = $config['smtp_from_name'] ?: 'Gestionale Condomini';
        $headers = "From: $fromName <$fromEmail>\r\n";
        $headers .= "Reply-To: $fromEmail\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $htmlBody = self::wrapHtml($subject, $body);
        $ok = @mail($to, $subject, $htmlBody, $headers);
        return $ok ? true : 'Funzione mail() fallita';
    }

    private static function sendViaSMTP(string $to, string $subject, string $body, array $config): mixed
    {
        $host = $config['smtp_host'];
        $port = (int)$config['smtp_port'];
        $user = $config['smtp_user'];
        $pass = $config['smtp_password'];
        $encryption = $config['smtp_encryption'];
        $fromEmail = $config['smtp_from_email'] ?: $user;
        $fromName = $config['smtp_from_name'] ?: 'Gestionale Condomini';

        if (empty($host)) return 'Host SMTP non configurato';

        $prefix = ($encryption === 'ssl') ? 'ssl://' : (($encryption === 'tls') ? 'tls://' : '');
        $errno = 0; $errstr = '';
        $context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);

        $actualPrefix = ($encryption === 'ssl') ? 'ssl://' : '';
        $sock = @stream_socket_client($actualPrefix . $host . ':' . $port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        if (!$sock) return "Connessione SMTP fallita: $errstr ($errno)";

        $resp = self::smtpRead($sock);
        self::smtpWrite($sock, "EHLO " . gethostname());
        $resp = self::smtpRead($sock);

        if ($encryption === 'tls') {
            self::smtpWrite($sock, "STARTTLS");
            $resp = self::smtpRead($sock);
            if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($sock);
                return 'STARTTLS fallito';
            }
            self::smtpWrite($sock, "EHLO " . gethostname());
            $resp = self::smtpRead($sock);
        }

        if ($user) {
            self::smtpWrite($sock, "AUTH LOGIN");
            self::smtpRead($sock);
            self::smtpWrite($sock, base64_encode($user));
            self::smtpRead($sock);
            self::smtpWrite($sock, base64_encode($pass));
            $resp = self::smtpRead($sock);
            if (strpos($resp, '235') === false) {
                fclose($sock);
                return 'Autenticazione SMTP fallita';
            }
        }

        self::smtpWrite($sock, "MAIL FROM:<$fromEmail>");
        $resp = self::smtpRead($sock);
        self::smtpWrite($sock, "RCPT TO:<$to>");
        $resp = self::smtpRead($sock);
        self::smtpWrite($sock, "DATA");
        self::smtpRead($sock);

        $htmlBody = self::wrapHtml($subject, $body);
        $boundary = md5(time());
        $msg = "From: $fromName <$fromEmail>\r\n";
        $msg .= "To: $to\r\n";
        $msg .= "Subject: $subject\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
        $msg .= "\r\n";
        $msg .= $htmlBody;
        $msg .= "\r\n.\r\n";

        self::smtpWrite($sock, $msg);
        $resp = self::smtpRead($sock);
        self::smtpWrite($sock, "QUIT");
        fclose($sock);

        if (strpos($resp, '250') !== false) return true;
        return "SMTP errore: $resp";
    }

    private static function smtpWrite($sock, string $data): void
    {
        fwrite($sock, $data . "\r\n");
    }

    private static function smtpRead($sock): string
    {
        $resp = '';
        while ($line = fgets($sock, 515)) {
            $resp .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $resp;
    }

    private static function wrapHtml(string $subject, string $body): string
    {
        $bodyHtml = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($subject) . '</title></head>'
             . '<body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;">'
             . '<div style="border-bottom:2px solid #0d6efd;padding-bottom:10px;margin-bottom:20px;">'
             . '<strong>Gestionale Condomini</strong></div>'
             . '<div>' . $bodyHtml . '</div>'
             . '<div style="margin-top:30px;padding-top:10px;border-top:1px solid #ddd;font-size:12px;color:#999;">'
             . 'Questa email e stata inviata dal Gestionale Condomini.</div></body></html>';
    }

    public static function testConnection(): mixed
    {
        $config = self::getConfig();
        if (empty($config['smtp_attivo']) || $config['smtp_attivo'] === '0') {
            return 'SMTP non attivo. Verra usata la funzione mail() di PHP.';
        }
        if (empty($config['smtp_host'])) return 'Host SMTP non configurato';

        $host = $config['smtp_host'];
        $port = (int)$config['smtp_port'];
        $encryption = $config['smtp_encryption'];

        $actualPrefix = ($encryption === 'ssl') ? 'ssl://' : '';
        $context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
        $errno = 0; $errstr = '';
        $sock = @stream_socket_client($actualPrefix . $host . ':' . $port, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
        if (!$sock) return "Connessione fallita: $errstr ($errno)";

        $resp = self::smtpRead($sock);
        fclose($sock);
        if (strpos($resp, '220') !== false) return true;
        return "Risposta inattesa: $resp";
    }

    public static function log(?int $comId, string $email, string $oggetto, string $stato, ?string $errore = null): void
    {
        global $pdo;
        try {
            $stmt = $pdo->prepare('INSERT INTO email_log (comunicazione_id, destinatario_email, oggetto, stato, errore) VALUES (:cid, :email, :ogg, :stato, :err)');
            $stmt->execute([
                'cid' => $comId,
                'email' => $email,
                'ogg' => $oggetto,
                'stato' => $stato,
                'err' => $errore,
            ]);
        } catch (Exception $e) {
            // ignore log failures
        }
    }

    public static function getLogs(int $limit = 100): array
    {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM email_log ORDER BY created_at DESC LIMIT :lim');
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
