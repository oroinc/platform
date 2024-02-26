<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * Abstract implementation of page state entity.
 */
#[ORM\MappedSuperclass]
class AbstractPageState
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    protected ?AbstractUser $user = null;

    /**
     * Base64 encoded page URL.
     * Max URL length set in application to 8190. Max Base64 encoded string length is 8190 * 4/3 = 10920
     */
    #[ORM\Column(name: 'page_id', type: Types::STRING, length: 10920)]
    protected ?string $pageId = null;

    /**
     * Hash of page id, used for quick access/search
     */
    #[ORM\Column(name: 'page_hash', type: Types::STRING, length: 32, unique: true)]
    protected ?string $pageHash = null;

    #[ORM\Column(name: 'data', type: Types::TEXT, nullable: false)]
    protected ?string $data = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $updatedAt = null;

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
     * @param AbstractUser|null $user
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
    public static function generateHash(string $pageId, string $userId): string
    {
        return md5(sprintf('%s_%s', $pageId, $userId));
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
     */
    #[ORM\PrePersist]
    public function doPrePersist()
    {
        $this->pageHash = self::generateHash($this->pageId, $this->user->getId());
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
    }

    /**
     * Pre update event handler
     */
    #[ORM\PreUpdate]
    public function doPreUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
