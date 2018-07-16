<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\PropertyAccess\PropertyAccess;

class EmailTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed  $value
     * @param mixed  $expectedValue
     */
    public function testSettersAndGetters($property, $value, $expectedValue = null)
    {
        if (!$expectedValue) {
            $expectedValue = $value;
        }

        $obj = new Email();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertEquals($expectedValue, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider()
    {
        return [
            ['gridName', 'testGridName'],
            ['entityClass', 'testEntityClass'],
            ['entityId', 123],
            ['from', 'test@example.com'],
            ['to', ['testGridNameTo']],
            ['cc', ['testGridNameCc']],
            ['bcc', ['testGridNameBcc']],
            ['subject', 'testSubject'],
            ['body', 'testBody'],
            ['gridName', 'testGridName'],
            ['template', new EmailTemplate('test')],
            ['contexts', new ArrayCollection([new \stdClass()])],
            ['contexts', [new \stdClass()], new ArrayCollection([new \stdClass()])],
            ['contexts', null, new ArrayCollection()],
            ['organization', new Organization()]
        ];
    }

    public function testGetEmptyRecipients()
    {
        $obj = new Email();
        $accessor = PropertyAccess::createPropertyAccessor();
        $this->assertEquals([], $accessor->getValue($obj, 'to'));
        $this->assertEquals([], $accessor->getValue($obj, 'cc'));
        $this->assertEquals([], $accessor->getValue($obj, 'bcc'));
    }

    public function testHasEntity()
    {
        $obj = new Email();
        $this->assertFalse($obj->hasEntity());

        $obj->setEntityClass('Test\Entity');
        $this->assertFalse($obj->hasEntity());

        $obj->setEntityId(123);
        $this->assertTrue($obj->hasEntity());

        $obj->setEntityClass(null);
        $this->assertFalse($obj->hasEntity());
    }
}
