<?php
namespace App\UI\Post;

use Nette;
use Nette\Application\UI\Form;

final class PostPresenter extends Nette\Application\UI\Presenter
{
    public function __construct(
        private Nette\Database\Explorer $database,
    ) {
    }

    // Metoda renderShow vyžaduje jeden argument – ID jednoho konkrétního článku, který má být zobrazen. Poté tento článek načte z databáze a předá ho do šablony.
    public function renderShow(int $id): void
    {
        $post = $this->database
            ->table('posts')
            ->get($id);
        if (!$post) {
            $this->error('Stránka nebyla nalezena');
        }
        $this->template->post = $post;
    }

    protected function createTemplate(?string $class = null): \Nette\Application\UI\Template
    {
        /** @var \Nette\Application\UI\Template $template */
        $template = parent::createTemplate($class);

        // Přidání filtru pro formátování data
        $template->addFilter('czDate', function ($date) {
            $formatter = new \IntlDateFormatter(
                'cs_CZ',                       // Lokalizace: Čeština
                \IntlDateFormatter::FULL,      // Styl data (dlouhý)
                \IntlDateFormatter::NONE       // Bez času
            );
            $formatter->setPattern('EEEE d. MMMM yyyy'); // Formát: Pá 17. květen 2024
            return $formatter->format(new \DateTime($date)); // Načtení datumu
        });

        return $template;
    }

}
