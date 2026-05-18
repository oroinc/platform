<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

#[ORM\Entity]
#[ORM\Table(name: 'test_api_activity_target')]
#[ORM\Index(columns: ['name'], name: 'test_api_at_name_idx')]
#[Config]
class TestActivityTarget implements TestFrameworkEntityInterface
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, nullable: true)]
    public ?string $name = null;
}
