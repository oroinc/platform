<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Base entity for integer index.
 */
#[ORM\MappedSuperclass]
abstract class AbstractIndexInteger implements ItemFieldInterface
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'field', type: Types::STRING, length: 250, nullable: false)]
    protected ?string $field = null;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'value', type: Types::BIGINT, nullable: false)]
    protected $value;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    #[\Override]
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    #[\Override]
    public function getField()
    {
        return $this->field;
    }

    #[\Override]
    public function setValue($value)
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException('Value must be a number');
        }

        $this->value = $value;

        return $this;
    }

    #[\Override]
    public function getValue()
    {
        return $this->value;
    }

    #[\Override]
    public function setItem(AbstractItem $index = null)
    {
        $this->item = $index;

        return $this;
    }

    #[\Override]
    public function getItem()
    {
        return $this->item;
    }
}
