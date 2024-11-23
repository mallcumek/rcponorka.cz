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

    public function renderDefault(): void
    {
        $this->template->posts = $this->database
            ->table('posts')
            ->order('created_at DESC')
            ->limit(5);
    }

    // ...
}
