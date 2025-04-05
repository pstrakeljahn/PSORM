<?php

namespace PS\Core\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PS\Core\Helper\Env;
use PS\Core\Helper\MailHelper;
use PS\Core\Logging\Logging;
use Exception;

/**
 * Class Mail
 *
 * A wrapper for PHPMailer to simplify sending emails with unified config, logging and backup.
 */
class Mail
{
    private PHPMailer $mailer;
    private Logging $log;

    private array $arrReceiver = [];
    private ?string $subject = null;
    private ?string $plainText = null;
    private ?string $htmlContent = null;

    /**
     * Mail constructor.
     *
     * Initializes the PHPMailer instance with SMTP settings from environment variables.
     */
    public function __construct()
    {
        $this->log = Logging::getInstance();

        // Validate config
        $requiredVars = ['MAIL_HOST', 'MAIL_USER', 'MAIL_PASS', 'MAIL_PORT', 'MAIL_FROM_NAME'];
        foreach ($requiredVars as $var) {
            if (is_null(Env::get($var))) {
                $this->log->add(Logging::LOG_TYPE_MAIL, "Mail server configuration missing: $var", true);
            }
        }

        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host       = Env::get('MAIL_HOST');
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = Env::get('MAIL_USER');
        $this->mailer->Password   = Env::get('MAIL_PASS');
        $this->mailer->SMTPSecure = 'tls';
        $this->mailer->Port       = (int) Env::get('MAIL_PORT');
        $this->mailer->setFrom(Env::get('MAIL_USER'), Env::get('MAIL_FROM_NAME'));
        $this->mailer->isHTML(true);
        $this->mailer->CharSet = 'UTF-8';
    }

    public function getMail(): PHPMailer
    {
        return $this->mailer;
    }

    public function addReceiver(string $mailAddress): self
    {
        $this->arrReceiver[] = $mailAddress;
        return $this;
    }

    public function getReceivers(): array
    {
        return $this->arrReceiver;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setContent(string $plainText): self
    {
        $this->plainText = $plainText;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->plainText;
    }

    public function setContentHtml(string $htmlString): self
    {
        $this->htmlContent = $htmlString;
        return $this;
    }

    public function getContentHtml(): ?string
    {
        return $this->htmlContent;
    }

    /**
     * Adds an attachment to the email.
     *
     * @param string $filePath Full path to the file.
     * @param string|null $name Optional file name.
     * @return $this
     * @throws Exception If file does not exist.
     */
    public function addAttachment(string $filePath, ?string $name = null): self
    {
        if (!file_exists($filePath)) {
            throw new Exception("Attachment file not found: $filePath");
        }

        $name ??= basename($filePath);
        $this->mailer->addAttachment($filePath, $name);

        return $this;
    }

    public function getAttachments(): array
    {
        $paths = [];
        foreach ($this->mailer->getAttachments() as $attachment) {
            $paths[] = realpath($attachment[0]);
        }
        return $paths;
    }

    /**
     * Sends the email and logs it.
     *
     * @return bool
     * @throws Exception If required fields are missing.
     */
    public function send(): bool
    {
        if (
            empty($this->arrReceiver) ||
            empty($this->subject) ||
            (empty($this->plainText) && empty($this->htmlContent))
        ) {
            throw new Exception("Receiver, subject and content must be set before sending.");
        }

        try {
            $this->mailer->clearAllRecipients();
            foreach ($this->arrReceiver as $receiver) {
                $this->mailer->addAddress($receiver);
            }

            $this->mailer->Subject = $this->subject;
            $this->mailer->AltBody = $this->plainText ?? '';
            $this->mailer->Body    = $this->htmlContent ?? nl2br(htmlentities($this->plainText));

            if ($this->mailer->send()) {
                $this->log->add(
                    Logging::LOG_TYPE_MAIL,
                    sprintf("Mail sent successfully (%s)", implode(', ', $this->arrReceiver))
                );
                MailHelper::saveMailCopy($this);
            }

            return true;
        } catch (Exception $e) {
            $this->log->add(
                Logging::LOG_TYPE_MAIL,
                sprintf("Failed to send email: %s", $this->mailer->ErrorInfo)
            );
            return false;
        }
    }
}
