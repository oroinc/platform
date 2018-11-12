<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Entity;

use Doctrine\Common\Inflector\Inflector;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Config\Common\ConfigObject;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ChannelTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var Channel */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new Channel();
    }

    public function testProperties()
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

        $this->assertPropertyAccessors(new Channel(), $properties);
    }

    /**
     * @dataProvider integrationSettingFieldsProvider
     *
     * @param string $fieldName
     */
    public function testIntegrationSettings($fieldName)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $referenceGetter = Inflector::camelize('get_' . $fieldName . '_reference');
        $this->assertTrue(method_exists($this->entity, $referenceGetter));

        $value = $accessor->getValue($this->entity, $fieldName);
        $this->assertNotEmpty($value);

        $this->assertInstanceOf(ConfigObject::class, $value);

        $newValue = ConfigObject::create([]);
        $accessor->setValue($this->entity, $fieldName, $newValue);
        $this->assertNotSame($value, $this->entity->$referenceGetter());

        $this->assertEquals($newValue, $accessor->getValue($this->entity, $fieldName));
        $this->assertNotSame($newValue, $accessor->getValue($this->entity, $fieldName));
        $this->assertSame($newValue, $this->entity->$referenceGetter());
    }

    /**
     * @return array
     */
    public function integrationSettingFieldsProvider()
    {
        return [
            ['synchronizationSettings'],
            ['mappingSettings'],
        ];
    }
}
