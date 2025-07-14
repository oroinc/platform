<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Model;

use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use PHPUnit\Framework\TestCase;

class QueryDesignerTest extends TestCase
{
    public function testConstructWithDefaultParameters(): void
    {
        $queryDesigner = new QueryDesigner();

        self::assertNull($queryDesigner->getEntity());
        self::assertNull($queryDesigner->getDefinition());
    }

    public function testConstructWithCustomParameters(): void
    {
        $entity = 'Test\Entity';
        $definition = 'test definition';

        $queryDesigner = new QueryDesigner($entity, $definition);

        self::assertSame($entity, $queryDesigner->getEntity());
        self::assertSame($definition, $queryDesigner->getDefinition());
    }

    public function testSetEntity(): void
    {
        $entity = 'Test\Entity';

        $queryDesigner = new QueryDesigner();
        $queryDesigner->setEntity($entity);

        self::assertSame($entity, $queryDesigner->getEntity());
    }

    public function testSetDefinition(): void
    {
        $definition = 'test definition';

        $queryDesigner = new QueryDesigner();
        $queryDesigner->setDefinition($definition);

        self::assertSame($definition, $queryDesigner->getDefinition());
    }
}
