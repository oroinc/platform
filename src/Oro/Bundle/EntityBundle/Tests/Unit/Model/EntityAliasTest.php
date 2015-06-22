<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Model;

use Oro\Bundle\EntityBundle\Model\EntityAlias;

class EntityAliasTest extends \PHPUnit_Framework_TestCase
{
    public function testSuccessCreationAndGetters()
    {
        $entityAlias = new EntityAlias('alias', 'plural_alias');
        $this->assertEquals('alias', $entityAlias->getAlias());
        $this->assertEquals('plural_alias', $entityAlias->getPluralAlias());
    }

    /**
     * @dataProvider invalidArgumentsProvider
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidArguments($alias, $pluralAlias)
    {
        new EntityAlias($alias, $pluralAlias);
    }

    public function invalidArgumentsProvider()
    {
        return [
            [null, null],
            ['', null],
            [null, ''],
            ['', ''],
            ['alias', ''],
            ['', 'plural_alias'],
            ['Alias', 'plural_alias'],
            ['alias', 'PluralAlias'],
            ['my-alias', 'PluralAlias'],
            ['alias', 'plural-alias'],
            ['1alias', 'plural_alias'],
            ['alias', '1plural-alias']
        ];
    }
}
