<?php
namespace App\UI\Edit;

use Nette;
use Nette\Application\UI\Form;

final class EditPresenter extends Nette\Application\UI\Presenter
{
    // Mysql login
    public function __construct(
        private Nette\Database\Explorer $database,
    ) {
    }

    // Továrna na formulář pro Post události
    protected function createComponentPostForm(): Form
    {
        $form = new Form;
        $form->addText('title', 'Titulek:')
            ->setRequired();
        $form->addTextArea('content', 'Obsah:')
            ->setRequired();

        $form->addSubmit('send', 'Uložit a publikovat');
        $form->onSuccess[] = $this->postFormSucceeded(...);

        return $form;
    }

    // Tato metoda získá data z formuláře, vloží je do databáze, vytvoří zprávu pro uživatele o úspěšném uložení příspěvku a
    // přesměruje na stránku s novým příspěvkem, takže hned uvidíme, jak vypadá.
    private function postFormSucceeded(array $data): void
    {
        $post = $this->database
            ->table('posts')
            ->insert($data);

        $this->flashMessage("Příspěvek byl úspěšně publikován.", 'success');
        $this->redirect('Post:show', $post->id);
    }


}
