<?php

namespace PS\Core\Helper;

use Config;
use DateTime;
use PS\Core\Mail\Mail;

/**
 * Class MailHelper
 *
 * Utility to save a complete copy of an email (including metadata and attachments) as a JSON log.
 */
class MailHelper
{
    private const SERVER = 'server';
    private const MAIL   = 'mail';

    private const STRUCTURE = [
        self::SERVER => null,
        self::MAIL   => null,
    ];

    /**
     * Saves a full copy of the given mail (metadata + content + attachments) to the filesystem.
     *
     * @param Mail $mailInstance
     * @return void
     */
    public static function saveMailCopy(Mail $mailInstance): void
    {
        $dateTime = new DateTime();
        $mail     = $mailInstance->getMail();

        $data = self::STRUCTURE;
        $data[self::SERVER] = self::getMailServerInformation($mail);
        $data[self::MAIL]   = self::getMailInformation($mailInstance, $dateTime);

        // Build folder path with timestamp and receiver info
        $folderPath = Config::LOG_FOLDER . sprintf(
            'mails/%s-%s',
            $dateTime->format('Ymd_His'),
            implode('_', $mailInstance->getReceivers())
        );

        mkdir($folderPath, 0777, true);

        // Attachments
        if (!empty($mailInstance->getAttachments())) {
            mkdir($folderPath . '/attachments', 0777, true);
        }

        // Save JSON info
        file_put_contents(
            $folderPath . '/mail.json',
            json_encode($data, JSON_PRETTY_PRINT)
        );

        // Copy attachments
        foreach ($mailInstance->getAttachments() as $attachmentPath) {
            copy($attachmentPath, $folderPath . '/attachments/' . basename($attachmentPath));
        }
    }

    /**
     * Extracts server-related info from the underlying mailer.
     *
     * @param object $mail The PHPMailer instance or similar.
     * @return array
     */
    private static function getMailServerInformation(object $mail): array
    {
        return [
            'isSMTP'     => true,
            'host'       => $mail->Host,
            'smtpAuth'   => $mail->SMTPAuth,
            'smtpSecure' => $mail->SMTPSecure,
            'port'       => $mail->Port,
            'setFrom'    => [
                'mail' => Env::get('MAIL_USER'),
                'name' => Env::get('MAIL_FROM_NAME'),
            ],
            'isHTML'     => true,
            'charSet'    => $mail->CharSet,
        ];
    }

    /**
     * Extracts mail content and metadata from the mail instance.
     *
     * @param Mail $mailInstance
     * @param DateTime $dateTime
     * @return array
     */
    private static function getMailInformation(Mail $mailInstance, DateTime $dateTime): array
    {
        return [
            'subject'        => $mailInstance->getSubject(),
            'content'        => $mailInstance->getContent(),
            'contentHtml'    => $mailInstance->getContentHtml(),
            'receivers'      => $mailInstance->getReceivers(),
            'hasAttachments' => !empty($mailInstance->getAttachments()),
            'sendAt'         => $dateTime->format('Y-m-d H:i:s'),
        ];
    }
}
