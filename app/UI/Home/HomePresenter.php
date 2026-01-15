<?php

namespace App\UI\Home;

// V sekci use máme App\Model\PostFacade, tak si můžeme zápis v PHP kódu zkrátit na PostFacade.
// O tento objekt požádáme v konstruktoru, zapíšeme jej do vlastnosti $facade a použijeme v metodě renderDefault.
use App\Model\PostFacade;
use App\Model\ContactFacade;
use Nette\Application\UI\Form;
use Nette;

final class HomePresenter extends Nette\Application\UI\Presenter
{
    // Konstruktor pro získání databázového spojení
    // Třída PostFacade si v konstruktoru řekne o předání Nette\Database\Explorer a jelikož je tato třída v DI containeru zaregistrovaná, kontejner tuto instanci vytvoří a předá ji.
    // DI za nás takto vytvoří instanci PostFacade a předá ji v konstruktoru třídě HomePresenter, který si o něj požádal.
    public function __construct(
        private PostFacade $facade,
        private ContactFacade $contactFacade, // Přidáme si do konstruktoru i contact fasádu pro odesílání e-mailů
        // GPT: Presenter získá instanci ContactFacade z DI kontejneru (Nette ji vytvoří a předá, protože zná třídu Mailer jako závislost)
    )
    {
    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->currentYear = date('Y');
    }

    // Nyní načteme příspěvky z databáze a pošleme je do šablony, která je následně vykreslí jako HTML kód.
    // V šabloně nyní bude k dispozici proměnná $posts, ve které jsou příspěvky získané z databáze.
    public function renderDefault(): void
    {
        $posts = $this->facade->getPublicArticles()->limit(50);

        // Vytvoření pole formátovaných dat
        $formattedDates = [];
        foreach ($posts as $post) {
            $formattedDates[$post->id] = $this->facade->formatDate($post->eventdate);
        }

        // Předání dat do šablony
        $this->template->posts = $posts;
        $this->template->formattedDates = $formattedDates;
    }

    // Archiv proběhlých akcí se stránkováním
    public function renderArchiv(int $page = 1): void
    {
        $itemsPerPage = 20;

        // Vytvoření paginátoru
        $paginator = new Nette\Utils\Paginator;
        $paginator->setItemCount($this->facade->getPastArticles()->count('*'));
        $paginator->setItemsPerPage($itemsPerPage);
        $paginator->setPage($page);

        // Získání proběhlých akcí pro aktuální stránku
        $posts = $this->facade->getPastArticles()
            ->limit($paginator->getLength(), $paginator->getOffset());

        // Vytvoření pole formátovaných dat
        $formattedDates = [];
        foreach ($posts as $post) {
            $formattedDates[$post->id] = $this->facade->formatDate($post->eventdate);
        }

        // Předání dat do šablony
        $this->template->posts = $posts;
        $this->template->formattedDates = $formattedDates;
        $this->template->paginator = $paginator;
    }

    // Metoda pro vytvoření formuláře
    protected function createComponentContactForm(): Form
    {
        $form = new Form;
        $form->addText('name', 'Jméno:')
            ->setRequired('Zadejte jméno.');

        $form->addEmail('email', 'E-mail:')
            ->setRequired('Zadejte e-mail.');

        $form->addText('subject', 'Předmět:')
            ->setRequired('Zadejte předmět.');

        $form->addTextarea('message', 'Zpráva:')
            ->setRequired('Zadejte zprávu.');

        $form->addSubmit('send', 'Odeslat zprávu');

        $form->onSuccess[] = [$this, 'contactFormSucceeded'];  // GPT: callback po úspěšném odeslání

        return $form;
    }

    // Metoda pro zpracování úspěšného odeslání formuláře
    public function contactFormSucceeded(\stdClass $data): void
    {
        // Předá data do ContactFacade a odešle e-mail
        $this->contactFacade->sendMessage($data->email, $data->name, $data->subject, $data->message);

        // Nastaví flash zprávu o úspěchu (typ 'success' pro zelené zobrazení)
        $this->flashMessage('Zpráva byla úspěšně odeslána.', 'success');

        // Přesměruje zpět na tuto stránku s kotvou #contact, aby stránka scrollovala k formuláři
        $this->redirect('this#hero');  // redirect na aktuální stránku + kotva
    }








    // ...
}
