<?php

namespace App\Model;

use DateTime;
use Nette;

final class PostFacade
{
    // Ve třídě si pomocí konstruktoru necháme předat databázový Explorer. Využijeme tak síly DI containeru.
    public function __construct(
        private Nette\Database\Explorer $database,
    )
    {
    }

    public function getPublicArticles()
    {
        return $this->database
            ->table('posts')
            ->where('created_at < ', new \DateTime)
            ->order('created_at DESC');
    }
    public function getGalleryImages()
    {
        return $this->database
            ->table('gallery')
            ->order('id DESC');
    }
    public function getGalleryImage($id)
    {
        return $this->database
            ->table('gallery')
            ->get($id);
    }

    // Metoda pro formátování datumu do CZ
    public function formatDate(string|DateTime $date, string $locale = 'cs_CZ', string $pattern = 'EE d. MMMM yyyy'): string
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

    // Metoda, která smaže záznam z databáze podle ID.
    public function deletePost(int $id): void
    {
        $this->database->table('posts')->where('id', $id)->delete();
    }

}
