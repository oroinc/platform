<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS;

/**
 * @Entity
 * @Table(name="cms_articles")
 */
class CmsArticle
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="string", length=255)
     */
    public $topic;

    /**
     * @Column(type="text")
     */
    public $text;

    /**
     * @ManyToOne(targetEntity="CmsUser", inversedBy="articles")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;

    /**
     * @OneToMany(targetEntity="CmsComment", mappedBy="article")
     */
    public $comments;

    /**
     * @Version @column(type="integer")
     */
    public $version;

    /**
     * @OneToOne(targetEntity="CmsOrganization", inversedBy="address")
     * @JoinColumn(name="organization", referencedColumnName="id")
     */
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
