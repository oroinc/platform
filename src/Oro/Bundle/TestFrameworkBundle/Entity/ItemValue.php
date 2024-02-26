<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;

/**
* Entity that represents Item Value
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'test_search_item_value')]
#[Config(
    routeName: 'oro_test_item_value_index',
    routeView: 'oro_test_item_value_view',
    routeUpdate: 'oro_test_item_value_update'
)]
class ItemValue implements TestFrameworkEntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'values')]
    protected ?Item $entity = null;

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
