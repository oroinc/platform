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
}
