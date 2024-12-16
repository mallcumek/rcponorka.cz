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
        $form->addText('title', 'Název událostí:')
            ->setRequired();
        $form->addDate('eventdate', 'Datum konání:')
            ->setRequired();
        $form->addText('opentime', 'Otevřeno od:')
            ->setRequired();
        $form->addText('starttime', 'Začátek akce:')
            ->setRequired();
        $form->addInteger('onsiteprice', 'Cena na místě v CZK:')
            ->setRequired();
        $form->addInteger('presaleprice', 'Cena předprodeje v CZK:')
            ;
        $form->addText('tickets', 'Odkaz na vstupenky:')
            ;
        $form->addTextArea('content', 'Poznámky k události:')
            ;

        $form->addSubmit('send', 'Uložit a publikovat');
        $form->onSuccess[] = $this->postFormSucceeded(...);

        return $form;
    }

    // Tato metoda získá data z formuláře, vloží nebo je upraví do databáze, vytvoří zprávu pro uživatele o úspěšném uložení příspěvku a
    // přesměruje na stránku s novým příspěvkem, takže hned uvidíme, jak vypadá.
    private function postFormSucceeded(array $data): void
    {
        // Kde se však onen parametr id vezme? Jedná se o parametr, který byl vložen do metody renderEdit.
        $id = $this->getParameter('id');

        // Pokud je k dispozici parametr id, znamená to, že budeme upravovat příspěvek
        if ($id) {
            $post = $this->database
                ->table('posts')
                ->get($id);
            $post->update($data);
        //  Pokud parametr id není k dispozici, pak to znamená, že by měl být nový příspěvek přidán.
        } else {
            $post = $this->database
                ->table('posts')
                ->insert($data);
        }

        $this->flashMessage('Příspěvek byl úspěšně publikován.', 'success');
        $this->redirect('Post:show', $post->id);
    }


    // Přidáme novou stránku edit do presenteru EditPresenter
    public function renderEdit(int $id): void
    {
        $post = $this->database
            ->table('posts')
            ->get($id);

        if (!$post) {
            $this->error('Post not found');
        }

        $this->getComponent('postForm')
            ->setDefaults($post->toArray());
    }



}
