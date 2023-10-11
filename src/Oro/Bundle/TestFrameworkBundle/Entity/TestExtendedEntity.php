<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Extended entity with different types of extended fields and relations for tests
 *
 * @ORM\Table(name="test_extended_entity")
 * @ORM\Entity
 * @Config
 */
class TestExtendedEntity implements ExtendEntityInterface, TestFrameworkEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="regular_field", type="string", length=255, nullable=true)
     */
    protected $regularField;

    public function getId(): int
    {
        return $this->id;
    }

    public function getRegularField(): string
    {
        return $this->regularField;
    }

    public function setRegularField(string $regularField): void
    {
        $this->regularField = $regularField;
    }
}
