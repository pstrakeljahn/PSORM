<?php

namespace PS\Core\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PS\Core\Helper\MailHelper;
use PS\Core\Logging\Logging;

/**
 * Class Mail
 *
 * A wrapper for PHPMailer to simplify sending emails with basic configuration.
 */
class Mail
{
    private PHPMailer $mailer;
    private Logging $log;

    private ?array $arrReceiver = [];
    private ?string $subject = null;
    private ?string $plainText = null;
    private ?string $htmlContent = null;

    /**
     * Mail constructor.
     *
     * Initializes the PHPMailer instance with SMTP settings from environment variables.
     *
     * @throws \Exception if required environment variables are missing
     */
    public function __construct()
    {
        if (
            !isset($_ENV['MAIL_HOST']) &&
            !isset($_ENV['MAIL_USER']) &&
            !isset($_ENV['MAIL_PASS']) &&
            !isset($_ENV['MAIL_PORT']) &&
            !isset($_ENV['MAIL_FROM_ADDRESS']) &&
            !isset($_ENV['MAIL_FROM_NAME'])
        ) {
            $this->log->add(Logging::LOG_TYPE_MAIL, "Mail Server is not configured!", true);
        }

        $this->mailer = new PHPMailer(true);
        $this->log = Logging::getInstance();
        $this->mailer->isSMTP();
        $this->mailer->Host       = $_ENV['MAIL_HOST'];
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = $_ENV['MAIL_USER'];
        $this->mailer->Password   = $_ENV['MAIL_PASS'];
        $this->mailer->SMTPSecure = 'tls';
        $this->mailer->Port       = $_ENV['MAIL_PORT'];
        $this->mailer->setFrom($_ENV['MAIL_USER'], $_ENV['MAIL_FROM_NAME']);
        $this->mailer->isHTML(true);
        $this->mailer->CharSet = 'UTF-8';
    }

    public function getMail()
    {
        return $this->mailer;
    }

    /**
     * Sets the recipient email address.
     *
     * @param string $mailAddress The email address of the recipient.
     * @return $this
     */
    public function addReceiver(string $mailAddress): self
    {
        $this->arrReceiver[] = $mailAddress;
        return $this;
    }

    public function getReceivers(): array
    {
        return $this->arrReceiver;
    }

    /**
     * Sets the email subject.
     *
     * @param string $subject The subject of the email.
     * @return $this
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * Sets the plain text content of the email.
     *
     * @param string $plainText The plain text version of the email body.
     * @return $this
     */
    public function setContent(string $plainText): self
    {
        $this->plainText = $plainText;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->plainText;
    }

    /**
     * Sets the HTML content of the email.
     *
     * @param string $htmlString The HTML version of the email body.
     * @return $this
     */
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
     * @param string $filePath The full file path of the attachment.
     * @param string|null $name Optional name to display for the attached file.
     * @return $this
     * @throws \Exception If the file does not exist.
     */
    public function addAttachment(string $filePath, ?string $name = null): self
    {
        if (!file_exists($filePath)) {
            throw new \Exception("Attachment file not found: $filePath");
        }

        if ($name === null) {
            $name = basename($filePath);
        }

        $this->mailer->addAttachment($filePath, $name);
        return $this;
    }

    public function getAttachments(): array
    {
        $returnArray = [];
        foreach ($this->mailer->getAttachments() as $attchment) {
            $returnArray[] = realpath($attchment[0]);
        }

        return $returnArray;
    }

    /**
     * Sends the email.
     *
     * Validates that recipient, subject, and plain text content are set before sending.
     *
     * @return bool True if the email was successfully sent.
     * @throws \Exception if required fields are missing or sending fails.
     */
    public function send(): bool
    {
        if (!count($this->arrReceiver) || empty($this->subject) || (empty($this->plainText) && empty($this->htmlContent))) {
            throw new \Exception("Receiver, subject, and plain text content must be set before sending.");
        }

        try {
            $this->mailer->clearAllRecipients();
            foreach ($this->getReceivers() as $receiver) {
                $this->mailer->addAddress($receiver);
            }
            $this->mailer->Subject = $this->subject;
            $this->mailer->AltBody = $this->plainText ?? "";
            $this->mailer->Body    = $this->htmlContent ?? nl2br(htmlentities($this->plainText));

            if ($this->mailer->send()) {
                $this->log->add(Logging::LOG_TYPE_MAIL, sprintf("Mail send successfully (%s)", implode(", ", $this->getReceivers())));
                MailHelper::saveMailCopy($this);
            }

            return true;
        } catch (\Exception $e) {
            $this->log->add(Logging::LOG_TYPE_MAIL, sprintf("Failed to send email: %s", $this->mailer->ErrorInfo));
            return false;
        }
    }
}
