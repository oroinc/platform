<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

#[ORM\Entity]
#[ORM\Table(name: 'test_api_custom_magazine')]
class TestCustomMagazine implements TestFrameworkEntityInterface
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, TestCustomArticle>
     */
    #[ORM\ManyToMany(targetEntity: TestCustomArticle::class)]
    #[ORM\JoinTable(name: 'test_api_custom_magazine_articles')]
    #[ORM\JoinColumn(name: 'magazine_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'article_id', referencedColumnName: 'id')]
    private ?Collection $articles = null;

    #[ORM\ManyToOne(targetEntity: TestCustomArticle::class)]
    #[ORM\JoinColumn(name: 'best_article_id', referencedColumnName: 'id')]
    private ?TestCustomArticle $bestArticle = null;

    #[ORM\Column(name: 'issue_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $issueDate = null;

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

    /**
     * @return \DateTimeInterface|null
     */
    public function getIssueDate()
    {
        return $this->issueDate;
    }

    /**
     * @param \DateTimeInterface|null $issueDate
     */
    public function setIssueDate($issueDate)
    {
        $this->issueDate = $issueDate;
    }
}
