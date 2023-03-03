<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Config\Common\ConfigObject;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ChannelTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $transport = $this->createMock(Transport::class);

        $properties = [
            ['id', 123],
            ['name', 'test'],
            ['type', 'test'],
            ['transport', $transport],
            ['connectors', ['customer', 'product']],
            ['enabled', true, false],
            ['previouslyEnabled', true],
            ['defaultUserOwner', new User()],
            ['defaultBusinessUnitOwner', new BusinessUnit()],
            ['organization', new Organization()],
            ['editMode', Channel::EDIT_MODE_ALLOW, false],
        ];

        self::assertPropertyAccessors(new Channel(), $properties);
    }

    /**
     * @dataProvider integrationSettingFieldsProvider
     */
    public function testIntegrationSettings(string $fieldName): void
    {
        $entity = new Channel();

        $accessor = PropertyAccess::createPropertyAccessor();
        $referenceGetter = 'get' . ucfirst($fieldName) . 'Reference';
        self::assertTrue(method_exists($entity, $referenceGetter));

        $value = $accessor->getValue($entity, $fieldName);
        self::assertInstanceOf(ConfigObject::class, $value);

        $newValue = ConfigObject::create(['key' => 'val']);
        $accessor->setValue($entity, $fieldName, $newValue);
        self::assertNotSame($value, $entity->$referenceGetter());
        self::assertEquals($newValue, $accessor->getValue($entity, $fieldName));
        self::assertNotSame($newValue, $accessor->getValue($entity, $fieldName));
        self::assertSame($newValue, $entity->$referenceGetter());
    }

    public function integrationSettingFieldsProvider(): array
    {
        return [
            ['synchronizationSettings'],
            ['mappingSettings'],
        ];
    }
}
