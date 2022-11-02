<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ApiBundle\Form\Type\CollectionType;
use Oro\Bundle\ApiBundle\Form\Type\EntityCollectionType;
use Oro\Bundle\ApiBundle\Tests\Unit\Form\ApiFormTypeTestCase;

class EntityCollectionTypeTest extends ApiFormTypeTestCase
{
    public function testGetParent()
    {
        $type = new EntityCollectionType();
        self::assertEquals(CollectionType::class, $type->getParent());
    }
}
