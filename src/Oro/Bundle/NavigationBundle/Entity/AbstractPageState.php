<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * Abstract implementation of page state entity.
 *
 * @ORM\MappedSuperclass
 */
class AbstractPageState
{
    /**
     * @var integer $id
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var AbstractUser $user
     */
    protected $user;

    /**
     * Base64 encoded page URL.
     * Max URL length set in application to 8190. Max Base64 encoded string length is 8190 * 4/3 = 10920
     *
     * @var string $pageId
     *
     * @ORM\Column(name="page_id", type="string", length=10920)
     */
    protected $pageId;

    /**
     * Hash of page id, used for quick access/search
     *
     * @var string $pageHash
     *
     * @ORM\Column(name="page_hash", type="string", length=32, unique=true)
     */
    protected $pageHash;

    /**
     * @var string $data
     *
     * @ORM\Column(name="data", type="text", nullable=false)
     */
    protected $data;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    public function __construct()
    {
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user
     *
     * @param  AbstractUser $user
     * @return NavigationHistoryItem
     */
    public function setUser(AbstractUser $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return AbstractUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set page id
     *
     * @param  string    $pageId
     * @return PageState
     */
    public function setPageId($pageId)
    {
        $this->pageId = $pageId;

        return $this;
    }

    /**
     * Get page id
     *
     * @return string
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * Get page hash
     *
     * @return string
     */
    public function getPageHash()
    {
        return $this->pageHash;
    }

    /**
     * Generate unique hash by page id and user id
     */
    public static function generateHashUsingUser(string $pageId, string $userId): string
    {
        return md5(sprintf('%s_%s', $pageId, $userId));
    }

    /**
     * Generate unique hash for page id
     *
     * @param  string $pageId
     * @return string
     */
    public static function generateHash($pageId)
    {
        return md5($pageId);
    }

    /**
     * Set data
     *
     * @param  string    $data
     * @return PageState
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set createdAt
     *
     * @param  \DateTime $createdAt
     * @return PageState
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param  \DateTime $updatedAt
     * @return PageState
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Pre persist event handler
     *
     * @ORM\PrePersist
     */
    public function doPrePersist()
    {
        $this->pageHash = self::generateHashUsingUser($this->pageId, $this->user->getId());
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
    }

    /**
     * Pre update event handler
     *
     * @ORM\PreUpdate
     */
    public function doPreUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
