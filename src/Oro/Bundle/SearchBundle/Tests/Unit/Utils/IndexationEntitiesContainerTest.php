<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Utils;

use Oro\Bundle\SearchBundle\Utils\IndexationEntitiesContainer;
use PHPUnit\Framework\TestCase;

class IndexationEntitiesContainerTest extends TestCase
{
    private IndexationEntitiesContainer $container;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = new IndexationEntitiesContainer();
    }

    public function testClear(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();

        $this->container->addEntity($obj1);
        $this->container->addEntity($obj2);

        self::assertNotEmpty($this->container->getEntities());

        $this->container->clear();

        self::assertEmpty($this->container->getEntities());
    }

    public function testGetEntities(): void
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

    public function testAddEntity(): void
    {
        $obj = new \stdClass();

        $this->container->addEntity($obj);

        self::assertEquals([\stdClass::class => [spl_object_hash($obj) => $obj]], $this->container->getEntities());
    }

    public function testRemoveEntities(): void
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
