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
    protected function beforeRender()
    {
        // Uložení proměnný currentYear do šablony
        parent::beforeRender();
        $this->template->currentYear = date('Y');

        // Použije jiný layout pouze pro show.latte - důvod jsou jiná meta pravidla
        if ($this->getAction() === 'show') {
            $this->setLayout(__DIR__ . '/@post.latte');
        }

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
        // Verze formátování datumu do formátu "31.2.2025" pro meta title
        $formattedDateShort = $this->facade->formatDateShort($post->eventdate);
        $this->template->formattedDateShort = $formattedDateShort;
    }



}
