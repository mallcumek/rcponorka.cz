<?php

namespace App\UI\Home;

use Nette;

final class HomePresenter extends Nette\Application\UI\Presenter
{
    // Konstruktor pro získání databázového spojení
    public function __construct(
        private Nette\Database\Explorer $database,
    )
    {
    }

    // Nyní načteme příspěvky z databáze a pošleme je do šablony, která je následně vykreslí jako HTML kód.
    // V šabloně nyní bude k dispozici proměnná $posts, ve které jsou příspěvky získané z databáze.
    public function renderDefault(): void
    {
        $this->template->posts = $this->database
            ->table('posts')
            ->order('created_at DESC')
            ->limit(5);
    }

    // ...
}
