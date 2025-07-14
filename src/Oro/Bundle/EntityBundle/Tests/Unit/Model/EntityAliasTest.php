<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Model;

use Oro\Bundle\EntityBundle\Model\EntityAlias;
use PHPUnit\Framework\TestCase;

class EntityAliasTest extends TestCase
{
    public function testSuccessCreationAndGetters(): void
    {
        $entityAlias = new EntityAlias('alias', 'plural_alias');
        $this->assertEquals('alias', $entityAlias->getAlias());
        $this->assertEquals('plural_alias', $entityAlias->getPluralAlias());
    }
}
