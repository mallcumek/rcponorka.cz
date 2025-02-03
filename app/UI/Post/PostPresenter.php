<?php
namespace App\UI\Post;

use Nette;
use Nette\Application\UI\Form;
use App\Model\PostFacade;

final class PostPresenter extends Nette\Application\UI\Presenter
{
    public function __construct(
        private Nette\Database\Explorer $database, private PostFacade $facade
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

        // Použití formátování datumu z fasády
        // Vytvořená samostatná proměnna, i když by to šlo přidat do $post
        $formattedDate = $this->facade->formatDate($post->eventdate);
        $this->template->formattedDate = $formattedDate;
    }



}
