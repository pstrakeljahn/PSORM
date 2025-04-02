<?php

namespace PS\Core\Helper;

use Config;
use DateTime;
use PS\Core\Mail\Mail;

class MailHelper
{
    private const SERVER = "server";
    private const MAIL = "mail";

    private const STRUCTURE = [
        self::SERVER => null,
        self::MAIL => null,
    ];

    public static function saveMailCopy(Mail $mailInstance)
    {
        $dateTime = new DateTime();
        $mail = $mailInstance->getMail();
        $data = self::STRUCTURE;
        $data[self::SERVER] = self::getMailServerInformation($mail);
        $data[self::MAIL] = self::getMailInformation($mailInstance, $dateTime);
        $folderPath = Config::LOG_FOLDER . sprintf("mails/%s-%s", $dateTime->format("Ymd_His"), implode("_", $mailInstance->getReceivers()));
        mkdir($folderPath, 0777, true);
        if (count($mailInstance->getAttachments()) > 0) {
            mkdir($folderPath . "/attachments", 0777, true);
        }
        file_put_contents($folderPath . "/mail.json", json_encode($data, JSON_PRETTY_PRINT));
        foreach ($mailInstance->getAttachments() as $attachementPath) {
            copy($attachementPath, $folderPath . "/attachments/" . basename($attachementPath));
        }
    }

    private static function getMailServerInformation($mail): array
    {
        return [
            "isSMTP" => true,
            "host" => $mail->Host,
            "smtpAuth" => $mail->SMTPAuth,
            "smtpSecure" => $mail->SMTPSecure,
            "port" => $mail->Port,
            "setFrom" => [
                "mail" => Env::get('MAIL_USER'),
                "name" => Env::get('MAIL_FROM_NAME')
            ],
            "isHTML" => true,
            "charSet" => $mail->CharSet,
        ];
    }

    private static function getMailInformation(Mail $mailInstance, $dateTime): array
    {
        return [
            "subject" => $mailInstance->getSubject(),
            "content" => $mailInstance->getContent(),
            "contentHtml" => $mailInstance->getContentHtml(),
            "receivers" => $mailInstance->getReceivers(),
            "hasAttachments" => count($mailInstance->getAttachments()) > 0,
            "sendAt" => $dateTime->format("Y-m-d H:i:s")
        ];
    }
}
