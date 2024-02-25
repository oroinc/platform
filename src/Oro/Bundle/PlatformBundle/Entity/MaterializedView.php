<?php

namespace Oro\Bundle\PlatformBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\PlatformBundle\Entity\Repository\MaterializedViewEntityRepository;

/**
 * Represents a materialized view.
 * This is just a technical entity to keep track of created materialized views.
 */
#[ORM\Entity(repositoryClass: MaterializedViewEntityRepository::class)]
#[ORM\Table(name: 'oro_materialized_view')]
#[Config(mode: 'hidden')]
class MaterializedView implements DatesAwareInterface
{
    use DatesAwareTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 63)]
    protected ?string $name = null;

    #[ORM\Column(name: 'with_data', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $withData = false;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function isWithData(): bool
    {
        return $this->withData;
    }

    public function setWithData(bool $withData): self
    {
        $this->withData = $withData;

        return $this;
    }
}
