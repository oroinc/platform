<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="test_api_custom_magazine")
 */
class TestCustomMagazine implements TestFrameworkEntityInterface
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var Collection|TestCustomArticle[]
     *
     * @ORM\ManyToMany(targetEntity="TestCustomArticle")
     * @ORM\JoinTable(name="test_api_custom_magazine_articles",
     *      joinColumns={@ORM\JoinColumn(name="magazine_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="article_id", referencedColumnName="id")}
     * )
     */
    private $articles;

    /**
     * @var TestCustomArticle|null
     *
     * @ORM\ManyToOne(targetEntity="TestCustomArticle")
     * @ORM\JoinColumn(name="best_article_id", referencedColumnName="id")
     */
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
     * @return Collection|TestCustomArticle[]
     */
    public function getArticles()
    {
        return $this->articles;
    }

    /**
     * @param Collection|TestCustomArticle[] $articles
     */
    public function setArticles(Collection $articles)
    {
        $this->articles = $articles;
    }

    public function addArticle(TestCustomArticle $article)
    {
        if (!$this->articles->contains($article)) {
            $this->articles[] = $article;
        }
    }

    public function removeArticle(TestCustomArticle $article)
    {
        if ($this->articles->contains($article)) {
            $this->articles->removeElement($article);
        }
    }

    /**
     * @return TestCustomArticle|null
     */
    public function getBestArticle()
    {
        return $this->bestArticle;
    }

    /**
     * @param TestCustomArticle|null $bestArticle
     */
    public function setBestArticle($bestArticle)
    {
        $this->bestArticle = $bestArticle;
    }
}
