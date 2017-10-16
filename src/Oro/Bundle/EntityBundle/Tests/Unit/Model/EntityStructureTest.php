<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Model;

use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class EntityStructureTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        static::assertPropertyAccessors(new EntityStructure(), [
            ['label', 'label'],
            ['pluralLabel', 'pluralLabel'],
            ['alias', 'alias'],
            ['pluralAlias', 'pluralAlias'],
            ['className', 'className'],
            ['icon', 'icon'],
            ['fields', ['field1' => (new EntityFieldStructure())->setName('field1')]],
            ['options', ['option1' => true]],
            ['routes', ['view' => 'route']],
        ]);
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\OptionNotFoundException
     * @expectedExceptionMessage Option "unknown" not found
     */
    public function testGetOptionThrowsException()
    {
        $item = new EntityStructure();
        $this->assertFalse($item->hasOption('unknown'));
        $item->getOption('unknown');
    }
}
