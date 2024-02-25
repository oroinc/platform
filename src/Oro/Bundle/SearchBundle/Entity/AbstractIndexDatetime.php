<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* AbstractIndexDatetime abstract class
*
*/
#[ORM\MappedSuperclass]
abstract class AbstractIndexDatetime implements ItemFieldInterface
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'field', type: Types::STRING, length: 250, nullable: false)]
    protected ?string $field = null;

    #[ORM\Column(name: 'value', type: Types::DATETIME_MUTABLE, nullable: false)]
    protected ?\DateTimeInterface $value = null;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setItem(AbstractItem $item)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * {@inheritdoc}
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        if (!$value instanceof \DateTime) {
            throw new \InvalidArgumentException('Value has to be of \DateTime class');
        }

        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->value;
    }
}
