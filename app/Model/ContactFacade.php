<?php
declare(strict_types=1);

namespace App\Model;

use Nette\Mail\Mailer;
use Nette\Mail\Message;

final class ContactFacade
{
    public function __construct(
        private Mailer $mailer,  // Nette Mailer se postará o odeslání
    ) {
    }

    public function sendMessage(string $email, string $name, string $subject, string $message): void
    {
        $mail = new Message;
        // pevný From z tvé domény kvůli SPF/DMARC
        $mail->setFrom('no-reply@rcponorka.cz', 'RC Ponorka')

// reply půjde uživateli (jméno z formuláře zůstává)
            ->addReplyTo($email, $name)
        ->addTo('botsie@seznam.cz')            // první cílový e-mail
        ->addTo('martin.krsik@gmail.com')      // druhý cílový e-mail
        ->setSubject($subject)                 // předmět zprávy z formuláře
        ->setBody(
                "Jméno: {$name}\n" .
                "E-mail: {$email}\n\n" .
                "Zpráva:\n{$message}\n"
            );                   // text zprávy (čistý text)

        $this->mailer->send($mail);  // odešle e-mail pomocí zvolené metody (výchozí PHP mail)
    }
}
