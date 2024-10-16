<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* AbstractIndexText abstract class
*
*/
#[ORM\MappedSuperclass]
abstract class AbstractIndexText implements ItemFieldInterface
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: 'Item', inversedBy: 'textFields')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id', nullable: false)]
    protected ?AbstractItem $item = null;

    #[ORM\Column(name: 'field', type: Types::STRING, length: 250, nullable: false)]
    protected ?string $field = null;

    #[ORM\Column(name: 'value', type: Types::TEXT, nullable: false)]
    protected ?string $value = null;

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
        $this->value = $value;

        return $this;
    }

    #[\Override]
    public function getValue()
    {
        return $this->value;
    }

    #[\Override]
    public function setItem(AbstractItem $item)
    {
        $this->item = $item;

        return $this;
    }

    #[\Override]
    public function getItem()
    {
        return $this->item;
    }
}
