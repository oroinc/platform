<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;

class EnumValueProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var EnumValueProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = new EnumValueProvider($this->doctrineHelper);
    }

    public function testGetEnumChoices()
    {
        $enumClass = '\stdClass';
        $expected = ['id' => 'Name'];

        $this->assertEnumChoices($enumClass);
        $this->assertEquals($expected, $this->provider->getEnumChoices($enumClass));
    }

    public function testGetEnumChoicesByCode()
    {
        $code = 'test_enum';
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $expected = ['id' => 'Name'];

        $this->assertEnumChoices($enumClass);
        $this->assertEquals($expected, $this->provider->getEnumChoicesByCode($code));
    }

    /**
     * @param string $enumClass
     */
    protected function assertEnumChoices($enumClass)
    {
        $enum = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue')
            ->disableOriginalConstructor()
            ->getMock();
        $enum->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('id'));
        $enum->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Name'));
        $values = [$enum];

        $repo = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue($values));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->will($this->returnValue($repo));
    }

    public function testGetEnumValueByCode()
    {
        $code = 'test_enum';
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $id = 1;
        $instance = new \stdClass();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with($enumClass, $id)
            ->will($this->returnValue($instance));

        $this->assertEquals($instance, $this->provider->getEnumValueByCode($code, $id));
    }

    public function getDefaultEnumValuesByCode()
    {
        $code = 'test_enum';
        $enumClass = 'Extend\Entity\EV_Test_Enum';
        $id = 1;
        $instance = new \stdClass();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($enumClass)
            ->will($this->returnValue([$instance]));

        $this->assertEquals([$instance], $this->provider->getEnumValueByCode($code));
    }
}
