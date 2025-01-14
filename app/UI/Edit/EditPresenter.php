<?php
namespace App\UI\Edit;

use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Strings;

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
        // Přidáváme pole pro nahrávání souborů
        $form->addUpload('image', 'Obrázek:');
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

            // Získání původního názvu souboru
            $file = $data['image'];
            $originalName = $file->getSanitizedName();
            // Odstranění staré přípony (např. .jpeg)
            $imageNameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
            // Udelame novy nazev s webp pro ulozeni do mysql, protoze menime format
            $newImageNameWebp = $imageNameWithoutExtension . ".webp";
            $newImageNameWebp = strtolower($newImageNameWebp);
            $data['image'] = $newImageNameWebp;

            $post = $this->database
                ->table('posts')
                ->get($id);
            $post->update($data);
        //  Pokud parametr id není k dispozici, pak to znamená, že by měl být nový příspěvek přidán.
        } else {

            // Získání původního názvu souboru
            $file = $data['image'];
            $originalName = $file->getSanitizedName();
            // Odstranění staré přípony (např. .jpeg)
            $imageNameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
            //udelame novy nazev s webp pro ulozeni do mysql, protoze u resizu menime formát obrázku
            $newImageNameWebp = $imageNameWithoutExtension . ".webp";
            $newImageNameWebp = strtolower($newImageNameWebp);
            $originalNameStrtoLower = strtolower($originalName);
            // Ulož název souboru obrázku do pole
            $data['image'] = $newImageNameWebp;
            //Titulek projede funkci webalize na seo titulek - vynecha znaky, diakritiku, male pismo, mezery na pomlcky. blabla
            $title_slug = Strings::webalize($data['title']);
            $data['title_slug'] = $title_slug;

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
