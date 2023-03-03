<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\Fixtures\Dumper\Entity;

use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\Fixtures\Dumper\TestAbstractClass;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\Fixtures\Dumper\TestInterface;

/**
 * Test entity #2
 */
class TestEntity2 extends TestAbstractClass implements TestInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;
}
