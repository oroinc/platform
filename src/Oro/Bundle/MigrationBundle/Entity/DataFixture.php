<?php

namespace Oro\Bundle\MigrationBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Data fixture migration log entity
 */
#[ORM\Entity]
#[ORM\Table('oro_migrations_data')]
class DataFixture
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'class_name', type: Types::STRING, length: 255)]
    protected ?string $className = null;

    #[ORM\Column(name: 'version', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $version = null;

    #[ORM\Column(name: 'loaded_at', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $loadedAt = null;

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
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     * @return $this
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLoadedAt()
    {
        return $this->loadedAt;
    }

    /**
     * @param \DateTime $loadedAt
     * @return $this
     */
    public function setLoadedAt($loadedAt)
    {
        $this->loadedAt = $loadedAt;

        return $this;
    }

    /**
     * @param string $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
