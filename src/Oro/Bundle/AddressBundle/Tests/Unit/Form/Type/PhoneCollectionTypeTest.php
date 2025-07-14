<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\PhoneCollectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use PHPUnit\Framework\TestCase;

class PhoneCollectionTypeTest extends TestCase
{
    private PhoneCollectionType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new PhoneCollectionType();
    }

    public function testGetParent(): void
    {
        $this->assertEquals(CollectionType::class, $this->type->getParent());
    }

    public function testGetName(): void
    {
        $this->assertEquals('oro_phone_collection', $this->type->getName());
    }
}
