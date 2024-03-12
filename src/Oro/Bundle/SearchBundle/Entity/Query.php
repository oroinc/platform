<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Search query entity
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_search_query')]
#[ORM\HasLifecycleCallbacks]
class Query
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'entity', type: Types::STRING, length: 250)]
    private ?string $entity = null;

    #[ORM\Column(name: 'query', type: Types::TEXT)]
    private ?string $query = null;

    #[ORM\Column(name: 'result_count', type: Types::INTEGER)]
    private ?int $resultCount = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set from
     *
     * @param  string $entity
     * @return Query
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get from
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set query
     *
     * @param  string $query
     * @return Query
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get query
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set resultCount
     *
     * @param  integer $resultCount
     * @return Query
     */
    public function setResultCount($resultCount)
    {
        $this->resultCount = $resultCount;

        return $this;
    }

    /**
     * Get resultCount
     *
     * @return integer
     */
    public function getResultCount()
    {
        return $this->resultCount;
    }

    /**
     * Pre persist event listener
     */
    #[ORM\PrePersist]
    public function beforeSave()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Set createdAt
     *
     * @param  \DateTime $createdAt
     * @return Query
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
}
