<?php
namespace App\Model;

use Nette;

final class PostFacade
{
    // Ve třídě si pomocí konstruktoru necháme předat databázový Explorer. Využijeme tak síly DI containeru.
    public function __construct(
        private Nette\Database\Explorer $database,
    ) {
    }

    public function getPublicArticles()
    {
        return $this->database
            ->table('posts')
            ->where('created_at < ', new \DateTime)
            ->order('created_at DESC');
    }
}
