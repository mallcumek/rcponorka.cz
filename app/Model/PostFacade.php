<?php
namespace App\Model;
use DateTime;
use Nette;

final class PostFacade
{
    // Ve třídě si pomocí konstruktoru necháme předat databázový Explorer. Využijeme tak síly DI containeru.
    public function __construct(
        private Nette\Database\Explorer $database,
    ) {
    }

    public function getPublicArticles()
    {
        return $this->database
            ->table('posts')
            ->where('created_at < ', new \DateTime)
            ->order('created_at DESC');
    }

    // Metoda pro formátování datumu do CZ
    public function formatDate(string|DateTime $date, string $locale = 'cs_CZ', string $pattern = 'EEEE d. MMMM yyyy'): string
    {
        $formatter = new \IntlDateFormatter(
            $locale,                       // Lokalizace (výchozí čeština)
            \IntlDateFormatter::FULL,       // Styl datumu (dlouhý)
            \IntlDateFormatter::NONE        // Bez času
        );

        $formatter->setPattern($pattern); // Nastavení vlastního vzoru
        $dateTime = $date instanceof DateTime ? $date : new DateTime($date); // Převod na DateTime
        return $formatter->format($dateTime); // Vrací formátované datum
    }

}
