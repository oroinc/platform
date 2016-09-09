<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\OrganizationBundle\Twig\OwnerTypeExtension;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\User;

class OwnerTypeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OwnerTypeExtension
     */
    private $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider */
    private $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityOwnerAccessor;

    /**
     * Set up test environment
     */
    protected function setUp()
    {
        $this->entityOwnerAccessor = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityOwnerAccessor->expects($this->any())
            ->method('getOwner')
            ->willReturnCallback(
                function ($entity) {
                    return $entity->getOwner();
                }
            );

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()->getMock();
        $this->extension = new OwnerTypeExtension($this->configProvider, $this->entityOwnerAccessor);
    }

    public function testName()
    {
        $this->assertEquals('oro_owner_type', $this->extension->getName());
    }

    public function testGetOwnerType()
    {
        $entity = new BusinessUnit();
        $this->prepareConfigProvider(
            ['owner_type' => 'test_type'],
            'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit'
        );
        $this->assertEquals('test_type', $this->extension->getOwnerType($entity));
    }

    public function testWithoutOwnerType()
    {
        $entity = new BusinessUnit();
        $this->prepareConfigProvider(
            ['another_owner_type' => 'test_type'],
            'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit'
        );

        $this->assertNull($this->extension->getOwnerType($entity));
    }

    public function testGetFunctions()
    {
        $this->assertArrayHasKey('oro_get_owner_type', $this->extension->getFunctions());
        $this->assertArrayHasKey('oro_get_entity_owner', $this->extension->getFunctions());
        $this->assertArrayHasKey('oro_get_owner_field_name', $this->extension->getFunctions());
    }

    public function testGetEntityOwner()
    {
        $owner = new User();
        $entity = new Entity();
        $entity->setOwner($owner);
        $this->assertSame($owner, $this->extension->getEntityOwner($entity));
    }

    public function testGetOwnerFieldName()
    {
        $entity = new BusinessUnit();
        $this->prepareConfigProvider(
            ['owner_field_name' => 'test_field'],
            'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit'
        );
        $this->assertEquals('test_field', $this->extension->getOwnerFieldName($entity));
    }

    protected function prepareConfigProvider(array $configValues, $className)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigIdInterface $configId */
        $configId = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface');
        $entityConfig = new Config($configId);
        $entityConfig->setValues($configValues);
        $this->configProvider->expects($this->once())->method('hasConfig')->with($this->equalTo($className))
            ->will($this->returnValue(true));

        $this->configProvider->expects($this->once())->method('getConfig')->with($this->equalTo($className))
            ->will($this->returnValue($entityConfig));
    }
}
