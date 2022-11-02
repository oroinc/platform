<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Model;

use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;

class QueryDesignerTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructWithDefaultParameters()
    {
        $queryDesigner = new QueryDesigner();

        self::assertNull($queryDesigner->getEntity());
        self::assertNull($queryDesigner->getDefinition());
    }

    public function testConstructWithCustomParameters()
    {
        $entity = 'Test\Entity';
        $definition = 'test definition';

        $queryDesigner = new QueryDesigner($entity, $definition);

        self::assertSame($entity, $queryDesigner->getEntity());
        self::assertSame($definition, $queryDesigner->getDefinition());
    }

    public function testSetEntity()
    {
        $entity = 'Test\Entity';

        $queryDesigner = new QueryDesigner();
        $queryDesigner->setEntity($entity);

        self::assertSame($entity, $queryDesigner->getEntity());
    }

    public function testSetDefinition()
    {
        $definition = 'test definition';

        $queryDesigner = new QueryDesigner();
        $queryDesigner->setDefinition($definition);

        self::assertSame($definition, $queryDesigner->getDefinition());
    }
}
