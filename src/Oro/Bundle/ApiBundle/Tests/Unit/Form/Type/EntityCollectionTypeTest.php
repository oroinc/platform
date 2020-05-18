<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ApiBundle\Form\Type\CollectionType;
use Oro\Bundle\ApiBundle\Form\Type\EntityCollectionType;
use Symfony\Component\Form\Test\TypeTestCase;

class EntityCollectionTypeTest extends TypeTestCase
{
    public function testGetParent()
    {
        $type = new EntityCollectionType();
        self::assertEquals(CollectionType::class, $type->getParent());
    }
}
