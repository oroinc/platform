<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table(name="test_search_item_value")
 * @ORM\Entity
 * @Config(
 *      routeName="oro_test_item_value_index",
 *      routeView="oro_test_item_value_view",
 *      routeUpdate="oro_test_item_value_update"
 * )
 */
class ItemValue implements TestFrameworkEntityInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Item
     *
     * @ORM\ManyToOne(targetEntity="Item", inversedBy="values")
     */
    protected $entity;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Item $item
     * @return $this
     */
    public function setEntity(Item $item)
    {
        $this->entity = $item;

        return $this;
    }

    /**
     * @return Item
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
