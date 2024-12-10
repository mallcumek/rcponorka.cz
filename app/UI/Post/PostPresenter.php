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

}
