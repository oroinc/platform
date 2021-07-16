<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class TestMagazineModel2
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var Collection|TestArticleModel2[] */
    private $articles;

    /** @var TestArticleModel2|null */
    private $bestArticle;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return Collection|TestArticleModel2[]
     */
    public function getArticles()
    {
        return $this->articles;
    }

    public function addArticle(TestArticleModel2 $article)
    {
        if (!$this->articles->contains($article)) {
            $this->articles[] = $article;
        }
    }

    public function removeArticle(TestArticleModel2 $article)
    {
        if ($this->articles->contains($article)) {
            $this->articles->removeElement($article);
        }
    }

    /**
     * @return TestArticleModel2|null
     */
    public function getBestArticle()
    {
        return $this->bestArticle;
    }

    /**
     * @param TestArticleModel2|null $bestArticle
     */
    public function setBestArticle($bestArticle)
    {
        $this->bestArticle = $bestArticle;
    }
}
