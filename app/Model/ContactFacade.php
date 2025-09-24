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

// reply půjde uživateli co vymplnil formulář
            ->addReplyTo($email, $name)
        ->addTo('botsie@seznam.cz')
        ->addTo('martin.krsik@gmail.com')
        ->setSubject($subject)
        ->setBody(
                "Jméno: {$name}\n" .
                "E-mail: {$email}\n\n" .
                "Zpráva:\n{$message}\n"
            );

        $this->mailer->send($mail);  // odešle e-mail pomocí zvolené metody (výchozí PHP mail)
    }
}
