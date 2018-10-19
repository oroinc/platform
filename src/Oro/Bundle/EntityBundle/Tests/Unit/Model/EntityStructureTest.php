<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Model;

use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class EntityStructureTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new EntityStructure(), [
            ['id', 'id'],
            ['label', 'label'],
            ['pluralLabel', 'pluralLabel'],
            ['alias', 'alias'],
            ['pluralAlias', 'pluralAlias'],
            ['className', 'className'],
            ['icon', 'icon'],
            ['fields', [(new EntityFieldStructure())->setName('field1')]],
            ['options', ['option1' => true]],
            ['routes', ['view' => 'route']],
        ]);
    }

    public function testGetNotExistingOption()
    {
        $item = new EntityStructure();
        $this->assertFalse($item->hasOption('unknown'));
        $this->assertNull($item->getOption('unknown'));
    }
}
