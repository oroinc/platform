<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cms_articles')]
class CmsArticle
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public $id;

    #[ORM\Column(type: 'string', length: 255)]
    public $topic;

    #[ORM\Column(type: 'text')]
    public $text;

    #[ORM\Column(type: 'json')]
    public $types;

    #[ORM\ManyToOne(targetEntity: CmsUser::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    public $user;

    #[ORM\OneToMany(mappedBy: 'article', targetEntity: CmsComment::class)]
    public $comments;

    /**
     * Version
     */
    #[ORM\Column(type: 'integer')]
    public $version;

    #[ORM\OneToOne(inversedBy: 'address', targetEntity: CmsOrganization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id')]
    public $organization;

    public function setAuthor(CmsUser $author)
    {
        $this->user = $author;
    }

    public function addComment(CmsComment $comment)
    {
        $this->comments[] = $comment;
        $comment->setArticle($this);
    }

    /**
     * @param mixed $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }

    /**
     * @return mixed
     */
    public function getOrganization()
    {
        return $this->organization;
    }
}
