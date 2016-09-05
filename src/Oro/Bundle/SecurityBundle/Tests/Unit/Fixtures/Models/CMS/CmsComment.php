<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS;

/**
 * @Entity
 * @Table(name="cms_comments")
 */
class CmsComment
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="string", length=255)
     */
    public $topic;

    /**
     * @Column(type="string")
     */
    public $text;

    /**
     * @ManyToOne(targetEntity="CmsArticle", inversedBy="comments")
     * @JoinColumn(name="article_id", referencedColumnName="id")
     */
    public $article;

    /**
     * @OneToOne(targetEntity="CmsOrganization", inversedBy="address")
     * @JoinColumn(name="organization", referencedColumnName="id")
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
