<?php
namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\FlexibleEntityBundle\Entity\Mapping\AbstractEntityFlexibleValue;

/**
 * @ORM\Table(name="test_search_item_value")
 * @ORM\Entity
 */
class ItemValue extends AbstractEntityFlexibleValue
{
    /**
     * @var \Oro\Bundle\FlexibleEntityBundle\Entity\Attribute $attribute
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\FlexibleEntityBundle\Entity\Attribute")
     * @ORM\JoinColumn(name="attribute_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $attribute;

    /**
     * @var Customer $entity
     *
     * @ORM\ManyToOne(targetEntity="Item", inversedBy="values")
     */
    protected $entity;

    /**
     * Store options values
     *
     * @var options ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\FlexibleEntityBundle\Entity\AttributeOption")
     * @ORM\JoinTable(
     *     name="test_search_item_value_option",
     *     joinColumns={@ORM\JoinColumn(name="value_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="option_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $options;
}
