<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;

/**
* Entity that represents Item2
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'test_search_item2')]
#[Config(
    routeName: 'oro_test_item2_index',
    routeView: 'oro_test_item2_view',
    routeCreate: 'oro_test_item2_create',
    routeUpdate: 'oro_test_item2_update'
)]
class Item2 implements TestFrameworkEntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->id;
    }
}
