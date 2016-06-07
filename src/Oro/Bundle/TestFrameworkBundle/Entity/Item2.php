<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table(name="test_search_item2")
 * @ORM\Entity
 * @Config(
 *      routeName="oro_test_item2_index",
 *      routeView="oro_test_item2_view",
 *      routeCreate="oro_test_item2_create",
 *      routeUpdate="oro_test_item2_update"
 * )
 */
class Item2 implements TestFrameworkEntityInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

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
