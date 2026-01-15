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

    /**
     * Přesměrování starých URL na nové SEO-friendly URL
     * Stará URL: /post/show?id=123 → Nová URL: /123-nazev-postu
     */
    public function actionOldUrl(int $id): void
    {
        $post = $this->database->table('posts')->get($id);
        if (!$post) {
            $this->error('Stránka nebyla nalezena');
        }

        // 301 Permanent Redirect na novou URL
        $this->redirectPermanent('Post:show', [
            'id' => $post->id,
            'slug' => $post->title_slug
        ]);
    }

    // Metoda renderShow nyní přijímá i slug pro SEO-friendly URL
    // Formát URL: /123-nazev-postu
    // Post se načítá pouze podle ID, slug slouží jen pro SEO v URL
    public function renderShow(int $id, string $slug): void
    {
        $post = $this->database
            ->table('posts')
            ->get($id);
        if (!$post) {
            $this->error('Stránka nebyla nalezena');
        }

        // Validace slugu - pokud slug neodpovídá skutečnému slugu v DB,
        // přesměrujeme na správnou URL (canonical redirect pro SEO)
        if ($slug !== $post->title_slug) {
            $this->redirectPermanent('Post:show', [
                'id' => $post->id,
                'slug' => $post->title_slug
            ]);
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
