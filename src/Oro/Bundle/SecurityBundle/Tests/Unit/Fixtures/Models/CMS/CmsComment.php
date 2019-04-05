<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="cms_comments")
 */
class CmsComment
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $topic;

    /**
     * @ORM\Column(type="string")
     */
    public $text;

    /**
     * @ORM\ManyToOne(targetEntity="CmsArticle", inversedBy="comments")
     * @ORM\JoinColumn(name="article_id", referencedColumnName="id")
     */
    public $article;

    /**
     * @ORM\OneToOne(targetEntity="CmsOrganization", inversedBy="address")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id")
     */
    public $organization;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getIdentity()
    {
        return $this->id;
    }

    public function setArticle(CmsArticle $article)
    {
        $this->article = $article;
    }

    public function __toString()
    {
        return __CLASS__."[id=".$this->id."]";
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
