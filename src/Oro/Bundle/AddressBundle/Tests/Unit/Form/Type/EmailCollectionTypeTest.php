<?php
namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\EmailCollectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;

class EmailCollectionTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailCollectionType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new EmailCollectionType();
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::class, $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_email_collection', $this->type->getName());
    }
}
