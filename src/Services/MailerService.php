<?php
/**
 * MailerService — Envoi d'emails via PHPMailer (SMTP)
 *
 * Config via variables d'environnement (MAIL_HOST, MAIL_USER, etc.)
 *
 * Usage :
 *   $mailer = new MailerService();
 *   $mailer->sendInvoiceNotification($invoice, $client);
 */

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

class MailerService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true); // true = exceptions activées
        $this->configure();
    }

    // ---- Configuration SMTP depuis .env ----

    private function configure(): void
    {
        $m = $this->mailer;

        $m->isSMTP();
        $m->CharSet  = PHPMailer::CHARSET_UTF8;
        $m->Host     = env('MAIL_HOST', 'smtp.mailtrap.io');
        $m->Port     = (int) env('MAIL_PORT', 587);
        $m->Username = env('MAIL_USER', '');
        $m->Password = env('MAIL_PASS', '');

        // Authentification si credentials fournis
        if ($m->Username) {
            $m->SMTPAuth = true;
            $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $m->setFrom(
            env('MAIL_USER', 'noreply@fact2pdf.local'),
            env('MAIL_FROM_NAME', 'Fact2PDF')
        );

        // Timeout SMTP
        $m->Timeout = 10;

        // Debug en dev uniquement (0 = off, 2 = full)
        $m->SMTPDebug = env('APP_ENV') === 'development' ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
    }

    /**
     * Notifie le client qu'une nouvelle facture a été créée.
     *
     * @param array $invoice Données facture
     * @param array $client  Données client (doit avoir 'email' et 'name')
     * @throws MailException Si l'envoi échoue
     */
    public function sendInvoiceNotification(array $invoice, array $client): void
    {
        if (empty($client['email'])) {
            logMessage('warning', "MailerService: pas d'email pour client #{$client['id']}");
            return;
        }

        $m = clone $this->mailer; // Cloner pour réutilisabilité
        $m->clearAllRecipients();

        $m->addAddress($client['email'], $client['name']);
        $m->Subject = "Votre facture {$invoice['number']} — " . env('APP_NAME', 'Fact2PDF');
        $m->isHTML(true);
        $m->Body    = $this->buildInvoiceHtml($invoice, $client);
        $m->AltBody = $this->buildInvoiceText($invoice, $client);

        try {
            $m->send();
            logMessage('info', "Email facture {$invoice['number']} envoyé à {$client['email']}");
        } catch (MailException $e) {
            logMessage('error', "Email échec pour {$invoice['number']}: {$m->ErrorInfo}");
            throw $e; // Remonter pour gestion dans le controller
        }
    }

    /**
     * Envoie un email générique.
     *
     * @param string $to      Adresse destinataire
     * @param string $subject Sujet
     * @param string $html    Corps HTML
     * @param string $text    Corps texte (fallback)
     */
    public function send(string $to, string $subject, string $html, string $text = ''): void
    {
        $m = clone $this->mailer;
        $m->clearAllRecipients();
        $m->addAddress($to);
        $m->Subject = $subject;
        $m->isHTML(true);
        $m->Body    = $html;
        $m->AltBody = $text ?: strip_tags($html);
        $m->send();
    }

    // ---- Templates email ----

    private function buildInvoiceHtml(array $invoice, array $client): string
    {
        $appName = e(env('APP_NAME', 'Fact2PDF'));
        $appUrl  = env('APP_URL', '#');
        $num     = e($invoice['number']);
        $total   = formatMoney((float) $invoice['total']);
        $due     = formatDate($invoice['due_date']);
        $name    = e($client['name']);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head><meta charset="utf-8"><title>Facture {$num}</title></head>
        <body style="font-family:sans-serif;color:#212529;max-width:600px;margin:0 auto">
          <div style="background:#0d6efd;color:#fff;padding:24px;border-radius:8px 8px 0 0">
            <h1 style="margin:0;font-size:22px">{$appName}</h1>
          </div>
          <div style="background:#f8f9fa;padding:24px">
            <p>Bonjour <strong>{$name}</strong>,</p>
            <p>Votre facture <strong>{$num}</strong> est disponible.</p>
            <table style="width:100%;border-collapse:collapse;margin:16px 0">
              <tr>
                <td style="padding:8px;background:#fff;border:1px solid #dee2e6">Montant TTC</td>
                <td style="padding:8px;background:#fff;border:1px solid #dee2e6;font-weight:bold">{$total}</td>
              </tr>
              <tr>
                <td style="padding:8px;background:#f8f9fa;border:1px solid #dee2e6">Date d'échéance</td>
                <td style="padding:8px;background:#f8f9fa;border:1px solid #dee2e6">{$due}</td>
              </tr>
            </table>
            <a href="{$appUrl}/invoices/{$invoice['id']}/pdf"
               style="display:inline-block;background:#0d6efd;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none">
              Télécharger la facture PDF
            </a>
          </div>
          <div style="padding:16px;text-align:center;color:#6c757d;font-size:12px">
            {$appName} — Merci de votre confiance.
          </div>
        </body>
        </html>
        HTML;
    }

    private function buildInvoiceText(array $invoice, array $client): string
    {
        return sprintf(
            "Bonjour %s,\n\nVotre facture %s est disponible.\nMontant TTC : %s\nÉchéance : %s\n\nMerci,\n%s",
            $client['name'],
            $invoice['number'],
            formatMoney((float) $invoice['total']),
            formatDate($invoice['due_date']),
            env('APP_NAME', 'Fact2PDF')
        );
    }
}
