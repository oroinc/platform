<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroTestFrameworkBundle_Entity_TestExtendedEntity;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Extended entity with different types of extended fields and relations for tests
 * @mixin OroTestFrameworkBundle_Entity_TestExtendedEntity
 */
#[ORM\Entity]
#[ORM\Table(name: 'test_extended_entity')]
#[Config]
class TestExtendedEntity implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'regular_field', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $regularField = null;

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
