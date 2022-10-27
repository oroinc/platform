<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DenormalizedPropertyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * Entity for testing search engine
 *
 * @ORM\Table(name="test_search_product")
 * @ORM\Entity
 * @Config()
 */
class Product implements TestFrameworkEntityInterface, DenormalizedPropertyAwareInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=true)
     */
    protected $name;

    protected $denormalizedName;

    protected $nameLowercase;

    public function updateDenormalizedProperties(): void
    {
        $this->denormalizedName = mb_strtoupper($this->name);
        $this->nameLowercase = mb_strtolower($this->name);
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->updateDenormalizedProperties();
    }

    /**
     * @ORM\PreUpdate
     */
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
