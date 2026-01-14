<?php

declare(strict_types=1);

namespace App\UI\Dashboard;

use App\Model\PostFacade;
use Nette;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Utils\Strings;
use Nette\Utils\Image;
use App\UI\Accessory\RequireLoggedUser;
use Contributte\ImageStorage\ImageStoragePresenterTrait;


/**
 * Presenter for the dashboard view.
 * Ensures the user is logged in before access.
 */
final class DashboardPresenter extends Nette\Application\UI\Presenter
{
    // Incorporates methods to check user login status
    use RequireLoggedUser;
    // Add $imageStorage to templates (in order to use macros)
    use ImageStoragePresenterTrait;
    protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->currentYear = date('Y');
    }
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
        private Nette\Database\Explorer $database, private PostFacade $facade
    )
    {
    }

    // Továrna na formulář pro fotku galerie
    protected function createComponentGalleryForm(): Form
    {
        $form = new Form;
        $form->addTextArea('title', 'Název/popis fotky:')
            ->setHtmlAttribute('class', 'form-control border border-dark');
        // Přidáváme pole pro nahrávání souborů
        $form->addUpload('image', 'Obrázek:')
            ->setHtmlAttribute('class', 'form-control')
            ->addRule([$this, 'validateImage'], ' Soubor musí být obrázek a musí mít šířku alespoň 700 pixelů.');
        $form->addSubmit('send', 'Uložit a publikovat')
            ->setHtmlAttribute('class', 'btn btn-primary'); // Třída Bootstrap pro tlačítko
        $form->onSuccess[] = $this->galleryFormSucceeded(...);

        return $form;
    }

    // Validace formuláře - kontrola aby soubor byl obrázek a zároveň větší nebo rovno 1000px *chatGPT
    // Metoda musí být public, jinak nefunguje po odeslání formuláře.
    public function validateImage(Nette\Forms\Controls\UploadControl $control): bool
    {
        $file = $control->getValue(); // Získání souboru jako instance FileUpload

        // Kontrola, zda byl soubor správně nahrán a je obrázek
        if (!$file instanceof Nette\Http\FileUpload || !$file->isOk() || !$file->isImage()) {
            return false;
        }

        // Získání rozměrů obrázku
        $imageSize = @getimagesize($file->getTemporaryFile());
        if ($imageSize === false) {
            return false;
        }

        // Kontrola minimální šířky
        if ($imageSize[0] < 700) {
            // Nastavení dynamické chybové zprávy
            // * $control je objekt třídy Nette\Forms\Controls\UploadControl.
            // Tento objekt představuje formulářové pole pro nahrávání souboru. Je součástí Nette formulářů.
            // $control je předán do metody validateImage jako argument.
            // * addError je metoda třídy BaseControl, kterou UploadControl dědí.
            // Metoda přidá chybovou zprávu přímo k danému formulářovému prvku.
            // Tato chyba se pak zobrazí uživateli pod polem ve formuláři.
            // * sprintf() je nativní PHP funkce, která vrací řetězec formátovaný podle zadané šablony.
            // Šablona 'Nahraný obrázek je příliš malý, má šířku %d pixelů.' obsahuje místo %d, které je nahrazeno hodnotou $imageSize[0].
            $control->addError(sprintf(
                'Nahraný obrázek je příliš malý, má šířku %d pixelů.',
                $imageSize[0]
            ));
            return false;
        }

        return true;
    }

    // Tato metoda získá data z formuláře, vloží nebo je upraví do databáze, vytvoří zprávu pro uživatele o úspěšném uložení příspěvku a
    // přesměruje na stránku s novým příspěvkem, takže hned uvidíme, jak vypadá.
    private function galleryFormSucceeded(Form $form, array $data): void
    {
        // Kde se však onen parametr id vezme? Jedná se o parametr, který byl vložen do metody renderEdit.
        $id = $this->getParameter('id');

        // Pokud je k dispozici parametr id, znamená to, že budeme upravovat příspěvek
        if ($id) {
            // Získání příspěvku z databáze
            $post = $this->database
                ->table('gallery')
                ->get($id);

            // Získání nahraného souboru z formuláře
            /** @var Nette\Http\FileUpload $file */
            $file = $data['image'];

            // Pokud je obrázek nahraný a je platný
            if ($file->isOk()) {
                // Získání původního názvu souboru
                $originalName = $file->getSanitizedName();
                // Odstranění staré přípony (např. .jpeg)
                $imageNameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
                // Udelame novy nazev s webp pro ulozeni do mysql, protoze menime format
                $newImageNameWebp = $imageNameWithoutExtension . ".webp";
                $newImageNameWebp = strtolower($newImageNameWebp);
                $data['image'] = $newImageNameWebp;
            } else {
                // Pokud obrázek nebyl nahrán, odstraníme klíč 'image' z dat
                unset($data['image']);
            }

            // Aktualizace příspěvku v databázi
            $post->update($data);
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
                ->table('gallery')
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
        $this->redirect('Gallery:show', $post->id);
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


        $uploadDir = __DIR__ . '/../../../www/gallery_data';


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
        $newImageNameWebp1920wmax = $imageNameWithoutExtension . "-1920wmax.webp";
        $newImageNameWebp1920wmax = strtolower($newImageNameWebp1920wmax);
        $newImageNameWebp1000 = $imageNameWithoutExtension . "-1000w.webp";
        $newImageNameWebp1000 = strtolower($newImageNameWebp1000);
        $newImageNameWebp800 = $imageNameWithoutExtension . "-800w.webp";
        $newImageNameWebp800 = strtolower($newImageNameWebp800);
        $newImageNameWebp400 = $imageNameWithoutExtension . "-400w.webp";
        $newImageNameWebp400 = strtolower($newImageNameWebp400);


        // Přečtení obsahu souboru z objektu FileUpload
        $fileContent = $file->getContents();
        // Vytvoření instance třídy Image pro manipulaci s obrázkem
        $image = Image::fromString($fileContent);


        //****************** Pro každou zmenšenou fotku zvlášť resize blok **********************

        // Vytvoření kopie původní instance obrázku a resize do max. šířky 1920px
        $thumb1920 = Image::fromString($image->toString());
        // pokud je obrazek vetsi nez 1920px tak ho resizni na 1920 a zbytek dopocitej
        if ($thumb1920->getWidth() >= 1920) {
            $thumb1920->resize(1920, null);
            $thumb1920->sharpen();
            $thumb1920->save($postDir . '/' . $newImageNameWebp1920wmax, 80, Image::WEBP);
        }
        // jinak ho ulož v původním rozlišení a převeď do .webp
        else{
            $image->sharpen();
            // Ulož soubor do složky "$uploadDir = __DIR__ . '/../../../www/data'" (resized)
            $image->save($postDir . '/' . $newImageNameWebp1920wmax, 80, Image::WEBP);
        }
        // Vytvoření kopie původní instance obrázku a resize do 1000w
        $thumb1000 = Image::fromString($image->toString());
        if ($thumb1000->getWidth() >= 1000) {
            $thumb1000->resize(1000, null);
            $thumb1000->sharpen();
            $thumb1000->save($postDir . '/' . $newImageNameWebp1000, 80, Image::WEBP);
        }
        // Vytvoření kopie původní instance obrázku a resize do 800w
        $thumb800 = Image::fromString($image->toString());
        if ($thumb800->getWidth() >= 800) {
            $thumb800->resize(800, null);
            $thumb800->sharpen();
            $thumb800->save($postDir . '/' . $newImageNameWebp800, 80, Image::WEBP);
        }
        // Vytvoření kopie původní instance obrázku a resize do 400w
        $thumb400 = Image::fromString($image->toString());
        if ($thumb400->getWidth() >= 400) {
            $thumb400->resize(400, null);
            $thumb400->sharpen();
            $thumb400->save($postDir . '/' . $newImageNameWebp400, 80, Image::WEBP);
        }
        //******************End Pro každou zmenšenou fotku zvášť resize blok **********************


        // Uloží do funkce string cesty s názvem souboru pro následné uložení do mysql. Strašně důležitý.
        return '/gallery_data/' . $postId . '/' . $newImageNameWebp;
    }

    //Tato metoda smaže příspěvek podle ID a přesměruje uživatele zpět na seznam příspěvků.
    public function handleDeleteGallery(int $id): void
    {
        $this->facade->deleteGallery($id);
        $this->flashMessage('Příspěvek z galerie byl úspěšně smazán.', 'success');
        $this->redirect('Gallery:default');
    }


    // Továrna na formulář pro fotku galerie
    protected function createComponentOpeningForm(): Form
    {
        $form = new Form;
        $form->addTextArea('content', 'Otevírací doba')
            ->setHtmlAttribute('rows', 3)
            ->setHtmlAttribute('class', 'form-control border border-dark');
        $form->addSubmit('send', 'Uložit otevírací dobu')
            ->setHtmlAttribute('class', 'btn btn-primary'); // Třída Bootstrap pro tlačítko
        // Načtení existujících dat z databáze
        $openingData = $this->database->table('openinghours')->fetch();
        if ($openingData) {
            $form->setDefaults([
                'content' => $openingData->content,
            ]);
        }
        $form->onSuccess[] = $this->openingFormSucceeded(...);
        return $form;
    }
    private function openingFormSucceeded(Form $form, array $data): void
    {
        // V databázi existuje jen jeden řádek s ID 1
        $id = 1;

        // Načtení řádku z databáze
        $post = $this->database->table('openinghours')->get($id);

        if (!$post) {
            $this->error('Řádek v tabulce openinghours neexistuje!');
        }

        // Aktualizace obsahu tabulky
        $post->update(['content' => $data['content']]);

        // Flash zpráva o úspěchu
        $this->flashMessage('Otevírací doba byla aktualizována.', 'success');
        $this->redirect('this');
    }






    // Přidáme novou stránku edit do presenteru EditPresenter
    public function renderEdit(int $id): void
    {
        // GPT komentáře

        // 1. Načteme záznam z databáze podle ID.
        // Metoda `table('posts')` vybírá tabulku `posts` a `get($id)` vrací konkrétní řádek podle hodnoty primárního klíče (v tomto případě $id).
        $post = $this->database
            ->table('gallery')
            ->get($id);

        // 2. Pokud záznam neexistuje (například špatné nebo neplatné ID), zobrazíme chybu 404.
        // Metoda `$this->error('...')` ukončí běh kódu a zobrazí stránku s chybovou zprávou.
        if (!$post) {
            $this->error('Post not found');
        }

        // 3. Nastavíme výchozí hodnoty formuláře podle dat příspěvku z databáze.
        // Komponenta `postForm` je formulář, který má metodu `setDefaults(array $values)`.
        // `toArray()` převede objekt `$post` na asociativní pole, aby ho formulář pochopil.
        $this->getComponent('galleryForm')
            ->setDefaults($post->toArray());

        // Přidáme pole $post do šablony
        $this->template->post = $post;

    }

}
