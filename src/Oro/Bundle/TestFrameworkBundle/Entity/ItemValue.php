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
 *      routeCreate="oro_test_item_value_create",
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
     * @return ItemValue
     */
    public function setEntity(Item $item)
    {
        $this->entity = $item;

        return $this;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}
