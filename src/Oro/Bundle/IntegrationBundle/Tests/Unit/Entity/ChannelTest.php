<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Entity;

use Doctrine\Common\Util\Inflector;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\DataGridBundle\Common\Object;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ChannelTest extends \PHPUnit_Framework_TestCase
{
    const TEST_STRING  = 'testString';
    const TEST_BOOLEAN = true;

    /** @var array */
    protected static $testConnectors = ['customer', 'product'];

    /** @var Channel */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new Channel();
    }

    protected function tearDown()
    {
        unset($this->entity);
    }

    /**
     * @dataProvider  getSetDataProvider
     *
     * @param string $property
     * @param mixed  $value
     * @param mixed  $expected
     */
    public function testSetGet($property, $value = null, $expected = null)
    {
        if ($value !== null) {
            call_user_func_array([$this->entity, 'set' . ucfirst($property)], [$value]);
        }

        $this->assertEquals($expected, call_user_func_array([$this->entity, 'get' . ucfirst($property)], []));
    }

    /**
     * @return array
     */
    public function getSetDataProvider()
    {
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        return [
            'id'               => ['id'],
            'name'             => ['name', self::TEST_STRING, self::TEST_STRING],
            'type'             => ['type', self::TEST_STRING, self::TEST_STRING],
            'connectors'       => ['connectors', self::$testConnectors, self::$testConnectors],
            'defaultUserOwner' => ['defaultUserOwner', $user, $user],
            'enabled'          => ['enabled', self::TEST_BOOLEAN, self::TEST_BOOLEAN],
        ];
    }

    public function testTransportRelation()
    {
        $transport = $this->getMockForAbstractClass('Oro\Bundle\IntegrationBundle\Entity\Transport');
        $this->assertAttributeEmpty('transport', $this->entity);

        $this->entity->setTransport($transport);
        $this->assertSame($transport, $this->entity->getTransport());

        $this->entity->clearTransport();
        $this->assertAttributeEmpty('transport', $this->entity);
    }

    /**
     * @dataProvider integrationSettingFieldsProvider
     *
     * @param string $fieldName
     */
    public function testIntegrationSettings($fieldName)
    {
        $accessor        = PropertyAccess::createPropertyAccessor();
        $referenceGetter = Inflector::camelize('get_' . $fieldName . '_reference');
        $this->assertTrue(method_exists($this->entity, $referenceGetter));

        $value = $accessor->getValue($this->entity, $fieldName);
        $this->assertNotEmpty($value);

        $this->assertInstanceOf('Oro\Bundle\DataGridBundle\Common\Object', $value);

        $newValue = Object::create([]);
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
            ['mappingSettings']
        ];
    }
}
