<?php

namespace App\UI\Edit;

use Nette;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Utils\Strings;
use Nette\Utils\Image;
use App\UI\Accessory\RequireLoggedUser;
use Contributte\ImageStorage\ImageStoragePresenterTrait;


final class EditPresenter extends Nette\Application\UI\Presenter
{
    // Incorporates methods to check user login status
    use RequireLoggedUser;
    // Add $imageStorage to templates (in order to use macros)
    use ImageStoragePresenterTrait;

    // Bez této funkce nelze použít "use ImageStoragePresenterTrait". Deklarace nejsou kompatibilní hlásí Tracy.
    // gpt: Abyste předešli konfliktu s metodou createTemplate z traitu, můžete v EditPresenter přepsat metodu createTemplate
    // Přepis metody createTemplate
    protected function createTemplate(?string $class = null): Nette\Application\UI\Template
    {
        $template = parent::createTemplate($class);
        // Přidání imageStorage do šablony (pokud je potřeba)
        $template->imageStorage = $this->imageStorage;
        return $template;
    }


    // Mysql login
    public function __construct(
        private Nette\Database\Explorer $database,
    )
    {
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
        $form->addInteger('presaleprice', 'Cena předprodeje v CZK:');
        $form->addText('tickets', 'Odkaz na vstupenky:');
        $form->addTextArea('content', 'Poznámky k události:');
        // Přidáváme pole pro nahrávání souborů
        $form->addUpload('image', 'Obrázek:');
        $form->addSubmit('send', 'Uložit a publikovat');
        $form->onSuccess[] = $this->postFormSucceeded(...);

        return $form;
    }

    // Tato metoda získá data z formuláře, vloží nebo je upraví do databáze, vytvoří zprávu pro uživatele o úspěšném uložení příspěvku a
    // přesměruje na stránku s novým příspěvkem, takže hned uvidíme, jak vypadá.
    private function postFormSucceeded(Form $form, array $data): void
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

        // Získání informací o nahrávaném souboru
        /** @var FileUpload $uploadedFile */
        $uploadedFile = $form['image']->getValue();

        // Pokud se soubor nahrál tak:

        if ($uploadedFile->isOk()) {
            //Volání Metody storeUploadedFile:
            //Volá metodu $this->storeUploadedFile($uploadedFile, $post->id).
            //Předává metodě $uploadedFile, což je instance třídy FileUpload, reprezentující nahrávaný soubor, a $post->id, což je identifikátor příspěvku, ke kterému soubor patří.
            $imagePath = $this->storeUploadedFile($uploadedFile, $post->id);
            //Uložení Vracené Cesty k Obrázku:
            //Návratová hodnota metody storeUploadedFile je přiřazena do proměnné $imagePath.
            //Tato hodnota představuje cestu k uloženému a případně zpracovanému souboru.
            $post->update(['image_path' => $imagePath]);

            // Získání původního názvu souboru * duplikovaný jako v hlavní podmínce výše u zápisu do DB, opravit. ale funguje.

            $originalName = $uploadedFile->getSanitizedName();
            // Odstranění staré přípony (např. .jpeg)
            $originalName = pathinfo($originalName, PATHINFO_FILENAME);
            //udelame novy nazev s webp pro ulozeni do mysql, protoze u resizu menime formát obrázku
            $originalNameWebp = $originalName . ".webp";
            $originalNameWebp = strtolower($originalNameWebp);
            $post->update(['image' => $originalNameWebp]);

            //Aktualizace Databázového Záznamu:
            //Aktualizuje databázový záznam příspěvku ($post) pomocí metody update.
            //Nová hodnota pole image_path je nastavena na hodnotu proměnné $imagePath, což je cesta k uloženému obrázku.
        }

        $this->flashMessage('Příspěvek byl úspěšně publikován.', 'success');
        $this->redirect('Post:show', $post->id);
    }

    private function clearDir(string $dir): void
    {
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    // Metoda pro uložení nahrávaného souboru na server
    private function storeUploadedFile(Nette\Http\FileUpload $file, int $postId): string
    {


        $uploadDir = __DIR__ . '/../../../www/data';


        // Vytvoření adresáře pro každý příspěvek cleardir maze puvodni soubory
        $postDir = $uploadDir . '/' . $postId;
        if (!is_dir($postDir)) {
            mkdir($postDir, 0777, true);

        } else {
            $this->clearDir($postDir);
        }

        // Získání původního názvu souboru
        $originalName = $file->getSanitizedName();
        // Odstranění staré přípony (např. .jpeg)
        $imageNameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);

        // Pouzito pro nize zakomentovanou verzi ukladani originalniho souboru na disk
        $originalImageNameStrtoLower = strtolower($originalName);

        // Udelame novy nazev malými písmeny s webp pro ulozeni do mysql, protoze u resizu menime formát obrázku. Tohle je největší width,zbytek bude jen pro srcset
        $newImageNameWebp = $imageNameWithoutExtension . ".webp";
        $newImageNameWebp = strtolower($newImageNameWebp);
        // Názvy pro menší obrázky webp do srcset, které následně uložíme jako soubory
        $newImageNameWebp1920 = $imageNameWithoutExtension . "-1920w.webp";
        $newImageNameWebp1920 = strtolower($newImageNameWebp1920);
        $newImageNameWebp1800 = $imageNameWithoutExtension . "-1800w.webp";
        $newImageNameWebp1800 = strtolower($newImageNameWebp1800);
        $newImageNameWebp1600 = $imageNameWithoutExtension . "-1600w.webp";
        $newImageNameWebp1600 = strtolower($newImageNameWebp1600);
        $newImageNameWebp1400 = $imageNameWithoutExtension . "-1400w.webp";
        $newImageNameWebp1400 = strtolower($newImageNameWebp1400);
        $newImageNameWebp1200 = $imageNameWithoutExtension . "-1200w.webp";
        $newImageNameWebp1200 = strtolower($newImageNameWebp1200);
        $newImageNameWebp1000 = $imageNameWithoutExtension . "-1000w.webp";
        $newImageNameWebp1000 = strtolower($newImageNameWebp1000);
        $newImageNameWebp800 = $imageNameWithoutExtension . "-800w.webp";
        $newImageNameWebp800 = strtolower($newImageNameWebp800);
        $newImageNameWebp600 = $imageNameWithoutExtension . "-600w.webp";
        $newImageNameWebp600 = strtolower($newImageNameWebp600);
        $newImageNameWebp400 = $imageNameWithoutExtension . "-400w.webp";
        $newImageNameWebp400 = strtolower($newImageNameWebp400);
        $newImageNameWebp200 = $imageNameWithoutExtension . "-200w.webp";
        $newImageNameWebp200 = strtolower($newImageNameWebp200);

        // Přečtení obsahu souboru z objektu FileUpload
        $fileContent = $file->getContents();
        // Vytvoření instance třídy Image pro manipulaci s obrázkem
        $image = Image::fromString($fileContent);

        /* Verze s ulozenim puvodniho obrazku na disk a nasledne cteni z disku na vytvoreni objektu

                // Přesun souboru do cílového adresáře
                 $file->move($postDir . '/' . $originalImageNameStrtoLower);
                // Vytvoření instance třídy Image pro manipulaci s obrázkem
                  $image = Image::fromFile($postDir . '/' . $originalImageNameStrtoLower);
        */

        //pokud je obrazek vetsi nez 1920px tak ho ulož v puvodni velikosti
        if ($image->getWidth() >= 1920) {
            $image->sharpen();
        }
        // Ulož soubor do složky "$uploadDir = __DIR__ . '/../../../../www/data'" (resized)
        $image->save($postDir . '/' . $newImageNameWebp, 80, Image::WEBP);

        //****************** Pro každou zmenšenou fotku zvášť resize blok **********************

        // Vytvoření kopie původní instance obrázku v 1800w
        $thumb1920 = Image::fromString($image->toString());
        //pokud je obrazek vetsi 1920px tak ho resizni na 1600 a zbytek dopocitej
        if ($thumb1920->getWidth() >= 1920) {
            $thumb1920->resize(1920, null);
            $thumb1920->sharpen();
        }
        $thumb1920->save($postDir . '/' . $newImageNameWebp1920, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 1800w
        $thumb1800 = Image::fromString($image->toString());
        if ($thumb1800->getWidth() >= 1800) {
            $thumb1800->resize(1800, null);
            $thumb1800->sharpen();
        }
        $thumb1800->save($postDir . '/' . $newImageNameWebp1800, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 1600w
        $thumb1600 = Image::fromString($image->toString());
        if ($thumb1600->getWidth() >= 1600) {
            $thumb1600->resize(1600, null);
            $thumb1600->sharpen();
        }
        $thumb1600->save($postDir . '/' . $newImageNameWebp1600, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 1400w
        $thumb1400 = Image::fromString($image->toString());
        if ($thumb1400->getWidth() >= 1400) {
            $thumb1400->resize(1400, null);
            $thumb1400->sharpen();
        }
        $thumb1400->save($postDir . '/' . $newImageNameWebp1400, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 1200w
        $thumb1200 = Image::fromString($image->toString());
        if ($thumb1200->getWidth() >= 1200) {
            $thumb1200->resize(1200, null);
            $thumb1200->sharpen();
        }
        $thumb1200->save($postDir . '/' . $newImageNameWebp1200, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 1000w
        $thumb1000 = Image::fromString($image->toString());
        if ($thumb1000->getWidth() >= 1000) {
            $thumb1000->resize(1000, null);
            $thumb1000->sharpen();
        }
        $thumb1000->save($postDir . '/' . $newImageNameWebp1000, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 800w
        $thumb800 = Image::fromString($image->toString());
        if ($thumb800->getWidth() >= 800) {
            $thumb800->resize(800, null);
            $thumb800->sharpen();
        }
        $thumb800->save($postDir . '/' . $newImageNameWebp800, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 600w
        $thumb600 = Image::fromString($image->toString());
        if ($thumb600->getWidth() >= 600) {
            $thumb600->resize(600, null);
            $thumb600->sharpen();
        }
        $thumb600->save($postDir . '/' . $newImageNameWebp600, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 400w
        $thumb400 = Image::fromString($image->toString());
        if ($thumb400->getWidth() >= 400) {
            $thumb400->resize(400, null);
            $thumb400->sharpen();
        }
        $thumb400->save($postDir . '/' . $newImageNameWebp400, 80, Image::WEBP);

        // Vytvoření kopie původní instance obrázku v 200w
        $thumb200 = Image::fromString($image->toString());
        if ($thumb200->getWidth() >= 200) {
            $thumb200->resize(200, null);
            $thumb200->sharpen();
        }
        $thumb200->save($postDir . '/' . $newImageNameWebp200, 80, Image::WEBP);

        //******************End Pro každou zmenšenou fotku zvášť resize blok **********************


        // Uloží do funkce string cesty s názvem souboru pro následné uložení do mysql. Strašně důležitý.
        return '/data/' . $postId . '/' . $newImageNameWebp;
    }

    // Přidáme novou stránku edit do presenteru EditPresenter
    public function renderEdit(int $id): void
    {
        // GPT komentáře

        // 1. Načteme záznam z databáze podle ID.
        // Metoda `table('posts')` vybírá tabulku `posts` a `get($id)` vrací konkrétní řádek podle hodnoty primárního klíče (v tomto případě $id).
        $post = $this->database
            ->table('posts')
            ->get($id);

        // 2. Pokud záznam neexistuje (například špatné nebo neplatné ID), zobrazíme chybu 404.
        // Metoda `$this->error('...')` ukončí běh kódu a zobrazí stránku s chybovou zprávou.
        if (!$post) {
            $this->error('Post not found');
        }

        // 3. Nastavíme výchozí hodnoty formuláře podle dat příspěvku z databáze.
        // Komponenta `postForm` je formulář, který má metodu `setDefaults(array $values)`.
        // `toArray()` převede objekt `$post` na asociativní pole, aby ho formulář pochopil.
        $this->getComponent('postForm')
            ->setDefaults($post->toArray());

        // Přidáme pole $post do šablony
        $this->template->post = $post;
    }


}
