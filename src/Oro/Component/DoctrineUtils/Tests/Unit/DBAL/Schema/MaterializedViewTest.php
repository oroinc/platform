<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\DBAL\Schema;

use Doctrine\DBAL\Platforms\PostgreSQL100Platform;
use Oro\Component\DoctrineUtils\DBAL\Schema\MaterializedView;

class MaterializedViewTest extends \PHPUnit\Framework\TestCase
{
    public function testProperties(): void
    {
        $name = 'sample_name';
        $definition = 'SELECT 1';
        $withData = true;
        $materializedView = new MaterializedView($name, $definition, $withData);


        self::assertEquals($name, $materializedView->getName());
        self::assertEquals('default.' . $name, $materializedView->getFullQualifiedName('default'));
        self::assertEquals($definition, $materializedView->getDefinition());
        self::assertTrue($materializedView->isWithData());
    }

    public function testGetQuotedName(): void
    {
        $name = 'all';
        $materializedView = new MaterializedView($name, 'SELECT 1', false);

        $platform = new PostgreSQL100Platform();
        self::assertEquals('"' . $name . '"', $materializedView->getQuotedName($platform));
    }
}
