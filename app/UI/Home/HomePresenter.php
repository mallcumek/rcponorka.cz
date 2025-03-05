<?php

namespace App\UI\Home;

// V sekci use máme App\Model\PostFacade, tak si můžeme zápis v PHP kódu zkrátit na PostFacade.
// O tento objekt požádáme v konstruktoru, zapíšeme jej do vlastnosti $facade a použijeme v metodě renderDefault.
use App\Model\PostFacade;

use Nette;

final class HomePresenter extends Nette\Application\UI\Presenter
{
    // Konstruktor pro získání databázového spojení
    // Třída PostFacade si v konstruktoru řekne o předání Nette\Database\Explorer a jelikož je tato třída v DI containeru zaregistrovaná, kontejner tuto instanci vytvoří a předá ji.
    // DI za nás takto vytvoří instanci PostFacade a předá ji v konstruktoru třídě HomePresenter, který si o něj požádal.
    public function __construct(
        private PostFacade $facade,
    )
    {
    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->currentYear = date('Y');
    }

    // Nyní načteme příspěvky z databáze a pošleme je do šablony, která je následně vykreslí jako HTML kód.
    // V šabloně nyní bude k dispozici proměnná $posts, ve které jsou příspěvky získané z databáze.
    public function renderDefault(): void
    {
        $posts = $this->facade->getPublicArticles()->limit(50);

        // Vytvoření pole formátovaných dat
        $formattedDates = [];
        foreach ($posts as $post) {
            $formattedDates[$post->id] = $this->facade->formatDate($post->eventdate);
        }

        // Předání dat do šablony
        $this->template->posts = $posts;
        $this->template->formattedDates = $formattedDates;
    }





    // ...
}
