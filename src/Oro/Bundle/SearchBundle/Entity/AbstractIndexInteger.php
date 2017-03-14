<?php

namespace Oro\Bundle\SearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractIndexInteger implements ItemFieldInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="field", type="string", length=250, nullable=false)
     */
    protected $field;

    /**
     * @var integer
     *
     * @ORM\Column(name="value", type="integer", nullable=false)
     */
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
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException('Value must be a number');
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

    /**
     * {@inheritdoc}
     */
    public function setItem(AbstractItem $index = null)
    {
        $this->item = $index;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem()
    {
        return $this->item;
    }
}
