<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Entity;

use Oro\Bundle\SearchBundle\Entity\UpdateEntity;

class UpdateEntityTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $entityInstance = new UpdateEntity();
        $this->assertNull($entityInstance->getEntity());
        $testEntity = 'test';
        $entityInstance->setEntity($testEntity);
        $this->assertSame($testEntity, $entityInstance->getEntity());
    }
}
