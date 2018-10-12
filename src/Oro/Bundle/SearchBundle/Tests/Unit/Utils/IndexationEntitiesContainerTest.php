<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Utils;

use Oro\Bundle\SearchBundle\Utils\IndexationEntitiesContainer;

class IndexationEntitiesContainerTest extends \PHPUnit\Framework\TestCase
{
    /** @var IndexationEntitiesContainer */
    private $container;

    protected function setUp()
    {
        $this->container = new IndexationEntitiesContainer();
    }

    public function testClear()
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $this->container->addEntity($obj1);
        $this->container->addEntity($obj2);

        self::assertNotEmpty($this->container->getEntities());

        $this->container->clear();

        self::assertEmpty($this->container->getEntities());
    }

    public function testGetEntities()
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $this->container->addEntity($obj1);
        $this->container->addEntity($obj2);

        self::assertNotEmpty($this->container->getEntities());
        self::assertEquals(
            [
                \stdClass::class => [
                    spl_object_hash($obj1) => $obj1,
                    spl_object_hash($obj2) => $obj2,
                ]
            ],
            $this->container->getEntities()
        );
    }

    public function testAddEntity()
    {
        $obj = new \stdClass();

        $this->container->addEntity($obj);

        self::assertAttributeEquals(
            [
                \stdClass::class => [
                    spl_object_hash($obj) => $obj,
                ]
            ],
            'entities',
            $this->container
        );
    }

    public function testRemoveEntities()
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $this->container->addEntity($obj1);
        $this->container->addEntity($obj2);

        self::assertNotEmpty($this->container->getEntities());

        $this->container->removeEntities(\stdClass::class);

        self::assertEmpty($this->container->getEntities());
    }
}
