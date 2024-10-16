<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DenormalizedPropertyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;

/**
 * Entity for testing search engine
 */
#[ORM\Entity]
#[ORM\Table(name: 'test_search_product')]
#[Config]
class Product implements TestFrameworkEntityInterface, DenormalizedPropertyAwareInterface
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, nullable: true)]
    protected ?string $name = null;

    protected $denormalizedName;

    protected $nameLowercase;

    #[\Override]
    public function updateDenormalizedProperties(): void
    {
        $this->denormalizedName = mb_strtoupper($this->name);
        $this->nameLowercase = mb_strtolower($this->name);
    }

    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->updateDenormalizedProperties();
    }

    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updateDenormalizedProperties();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getDenormalizedName()
    {
        return $this->denormalizedName;
    }

    public function getNameLowercase()
    {
        return $this->nameLowercase;
    }
}
