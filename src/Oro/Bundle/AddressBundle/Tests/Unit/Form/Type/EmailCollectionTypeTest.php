<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\EmailCollectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use PHPUnit\Framework\TestCase;

class EmailCollectionTypeTest extends TestCase
{
    private EmailCollectionType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new EmailCollectionType();
    }

    public function testGetParent(): void
    {
        $this->assertEquals(CollectionType::class, $this->type->getParent());
    }

    public function testGetName(): void
    {
        $this->assertEquals('oro_email_collection', $this->type->getName());
    }
}
