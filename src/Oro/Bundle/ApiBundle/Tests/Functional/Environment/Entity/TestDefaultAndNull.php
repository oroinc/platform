<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'test_api_default_and_null')]
class TestDefaultAndNull implements TestFrameworkEntityInterface
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column(name: 'with_default_value_string', type: Types::STRING, nullable: true)]
    public ?string $withDefaultValueString = null;

    #[ORM\Column(name: 'without_default_value_string', type: Types::STRING, nullable: true)]
    public ?string $withoutDefaultValueString = null;

    #[ORM\Column(name: 'with_default_value_boolean', type: Types::BOOLEAN, nullable: true)]
    public ?bool $withDefaultValueBoolean = null;

    #[ORM\Column(name: 'without_default_value_boolean', type: Types::BOOLEAN, nullable: true)]
    public ?bool $withoutDefaultValueBoolean = null;

    #[ORM\Column(name: 'with_default_value_integer', type: Types::INTEGER, nullable: true)]
    public ?int $withDefaultValueInteger = null;

    #[ORM\Column(name: 'without_default_value_integer', type: Types::INTEGER, nullable: true)]
    public ?int $withoutDefaultValueInteger = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'with_df_not_blank', type: Types::STRING, nullable: true)]
    public ?string $withDefaultValueAndNotBlank = null;

    #[Assert\NotNull]
    #[ORM\Column(name: 'with_df_not_null', type: Types::STRING, nullable: true)]
    public ?string $withDefaultValueAndNotNull = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'with_not_blank', type: Types::STRING, nullable: true)]
    public ?string $withNotBlank = null;

    #[Assert\NotNull]
    #[ORM\Column(name: 'with_not_null', type: Types::STRING, nullable: true)]
    public ?string $withNotNull = null;

    public function __construct()
    {
        $this->withDefaultValueString = 'default';
        $this->withDefaultValueBoolean = false;
        $this->withDefaultValueInteger = 0;
        $this->withDefaultValueAndNotBlank = 'default_NotBlank';
        $this->withDefaultValueAndNotNull = 'default_NotNull';
    }
}
