<?php

namespace App\UI\Gallery;

// V sekci use máme App\Model\PostFacade, tak si můžeme zápis v PHP kódu zkrátit na PostFacade.
// O tento objekt požádáme v konstruktoru, zapíšeme jej do vlastnosti $facade a použijeme v metodě renderDefault.
use App\Model\PostFacade;
use Nette;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Utils\Strings;
use Nette\Utils\Image;
use App\UI\Accessory\RequireLoggedUser;
use Contributte\ImageStorage\ImageStoragePresenterTrait;

final class GalleryPresenter extends Nette\Application\UI\Presenter
{
    // Konstruktor pro získání databázového spojení
    // Třída PostFacade si v konstruktoru řekne o předání Nette\Database\Explorer a jelikož je tato třída v DI containeru zaregistrovaná, kontejner tuto instanci vytvoří a předá ji.
    // DI za nás takto vytvoří instanci PostFacade a předá ji v konstruktoru třídě HomePresenter, který si o něj požádal.
    public function __construct(
        private PostFacade $facade,
    )
    {
    }



    // Nyní načteme příspěvky z databáze a pošleme je do šablony, která je následně vykreslí jako HTML kód.
    // V šabloně nyní bude k dispozici proměnná $posts, ve které jsou příspěvky získané z databáze.
    public function renderDefault(): void
    {
        $posts = $this->facade->getGalleryImages()->limit(50);
        // Předání dat do šablony
        $this->template->posts = $posts;

    }

    // Metoda renderShow vyžaduje jeden argument – ID jednoho konkrétního článku, který má být zobrazen. Poté tento článek načte z databáze a předá ho do šablony.
    public function renderShow(int $id): void
    {
        $post = $this->facade->getGalleryImage($id);
        if (!$post) {
            $this->error('Stránka nebyla nalezena');
        }
        $this->template->post = $post;

    }



    // ...
}
