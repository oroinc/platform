<?php

namespace Oro\Bundle\PlatformBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * Represents a materialized view.
 * This is just a technical entity to keep track of created materialized views.
 *
 * @ORM\Table(name="oro_materialized_view")
 * @ORM\Entity(repositoryClass="Oro\Bundle\PlatformBundle\Entity\Repository\MaterializedViewEntityRepository")
 * @Config(mode="hidden")
 */
class MaterializedView implements DatesAwareInterface
{
    use DatesAwareTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=63)
     */
    protected ?string $name = null;

    /**
     * @ORM\Column(type="boolean", name="with_data", options={"default"=false})
     */
    protected bool $withData = false;

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
