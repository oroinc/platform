<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionTranslation;

#[ORM\Entity]
#[ORM\Table(name: 'oro_enum_value_test')]
#[ORM\UniqueConstraint(name: 'oro_enum_value_test_uq', columns: ['enum_id', 'code'])]
#[Gedmo\TranslationEntity(class: EnumOptionTranslation::class)]
#[Config(
    defaultValues: [
        'grouping' => ['groups' => ['enum', 'dictionary']],
        'dictionary' => ['virtual_fields' => ['code', 'name']]
    ]
)]
class TestEnumValue extends EnumOption
{
    protected string $enumName;

    public function __construct(
        string $enumCode,
        string $name,
        string $internalId,
        int $priority = 0,
        bool $default = false
    ) {
        parent::__construct($enumCode, $name, $internalId, $priority, $default);

        $this->enumName = $name;
    }

    public function get(string $name): mixed
    {
    }

    public function set(string $name, mixed $value): static
    {
    }

    public function setName(string $name): static
    {
        parent::setName($name);
        $this->enumName = $name;

        return $this;
    }

    public function getDefaultName(): string
    {
        return $this->enumName;
    }
}
