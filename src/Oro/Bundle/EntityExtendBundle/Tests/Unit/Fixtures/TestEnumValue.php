<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;

#[ORM\Entity]
#[ORM\Table(name: 'oro_enum_value_test')]
#[ORM\UniqueConstraint(name: 'oro_enum_value_test_uq', columns: ['enum_id', 'code'])]
#[Gedmo\TranslationEntity(class: 'Oro\Bundle\EntityExtendBundle\Entity\EnumValueTranslation')]
#[Config(
    defaultValues: [
        'grouping' => ['groups' => ['enum', 'dictionary']],
        'dictionary' => ['virtual_fields' => ['code', 'name']]
    ]
)]
class TestEnumValue extends AbstractEnumValue implements ExtendEntityInterface
{
    public function get(string $name): mixed
    {
    }

    public function set(string $name, mixed $value): static
    {
    }
}
