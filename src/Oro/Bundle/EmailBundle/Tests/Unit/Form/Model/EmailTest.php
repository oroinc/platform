<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    /**
     * @dataProvider propertiesDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value, mixed $expectedValue = null): void
    {
        if (!$expectedValue) {
            $expectedValue = $value;
        }

        $obj = new Email();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        self::assertEquals($expectedValue, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider(): array
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

    public function testGetEmptyRecipients(): void
    {
        $obj = new Email();
        $accessor = PropertyAccess::createPropertyAccessor();
        self::assertEquals([], $accessor->getValue($obj, 'to'));
        self::assertEquals([], $accessor->getValue($obj, 'cc'));
        self::assertEquals([], $accessor->getValue($obj, 'bcc'));
    }

    public function testHasEntity(): void
    {
        $obj = new Email();
        self::assertFalse($obj->hasEntity());

        $obj->setEntityClass('Test\Entity');
        self::assertFalse($obj->hasEntity());

        $obj->setEntityId(123);
        self::assertTrue($obj->hasEntity());

        $obj->setEntityClass(null);
        self::assertFalse($obj->hasEntity());
    }

    public function testAllowToUpdateEmptyContexts(): void
    {
        $obj = new Email();
        self::assertTrue($obj->isUpdateEmptyContextsAllowed());

        $obj->setAllowToUpdateEmptyContexts(false);
        self::assertFalse($obj->isUpdateEmptyContextsAllowed());

        $obj->setAllowToUpdateEmptyContexts(true);
        self::assertTrue($obj->isUpdateEmptyContextsAllowed());
    }
}
