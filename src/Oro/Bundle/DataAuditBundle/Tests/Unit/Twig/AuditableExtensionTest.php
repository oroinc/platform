<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Entity;

use Oro\Bundle\DataAuditBundle\Twig\AuditableExtension;
use Oro\Bundle\DataAuditBundle\Tests\Unit\Fixture\LoggableClass;
use Oro\Bundle\DataAuditBundle\Tests\Unit\Fixture\__CG__\LoggableClass as LoggableClassProxy;

class AuditableExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $auditConfigProvider;

    /**
     * @var AuditableExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->auditConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new AuditableExtension($this->auditConfigProvider);
    }

    protected function tearDown()
    {
        unset($this->auditConfigProvider);
        unset($this->extension);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_auditable', $this->extension->getName());
    }

    public function testGetTests()
    {
        $twigTests = $this->extension->getTests();
        $this->assertCount(1, $twigTests);

        /** @var \Twig_SimpleTest $auditableTest */
        $auditableTest = current($twigTests);
        $this->assertEquals('auditable', $auditableTest->getName());
        $this->assertEquals(array($this->extension, 'isAuditable'), $auditableTest->getCallable());

    }

    /**
     * @param mixed $entity
     * @param string|null $expectedClass
     * @param boolean $expectedResult
     * @dataProvider isAuditableDataProvider
     */
    public function testIsAuditable($entity, $expectedClass, $expectedResult)
    {
        if ($expectedClass) {
            $entityConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
                ->getMockForAbstractClass();
            $entityConfig->expects($this->once())
                ->method('is')
                ->with('auditable')
                ->will($this->returnValue($expectedResult));

            $this->auditConfigProvider->expects($this->once())
                ->method('hasConfig')
                ->with($expectedClass)
                ->will($this->returnValue(true));
            $this->auditConfigProvider->expects($this->once())
                ->method('getConfig')
                ->with($expectedClass)
                ->will($this->returnValue($entityConfig));
        }

        $this->assertEquals($expectedResult, $this->extension->isAuditable($entity));
    }

    /**
     * @return array
     */
    public function isAuditableDataProvider()
    {
        return array(
            'not an object' => array(
                'entity'         => 'some string',
                'expectedClass'  => null,
                'expectedResult' => false,
            ),
            'not configurable object' => array(
                'entity'         => new LoggableClass(),
                'expectedClass'  => null,
                'expectedResult' => false,
            ),
            'not auditable object' => array(
                'entity'         => new LoggableClass(),
                'expectedClass'  => 'Oro\Bundle\DataAuditBundle\Tests\Unit\Fixture\LoggableClass',
                'expectedResult' => false,
            ),
            'auditable object' => array(
                'entity'         => new LoggableClass(),
                'expectedClass'  => 'Oro\Bundle\DataAuditBundle\Tests\Unit\Fixture\LoggableClass',
                'expectedResult' => true,
            ),
            'auditable proxy' => array(
                'entity'         => new LoggableClassProxy(),
                'expectedClass'  => 'LoggableClass',
                'expectedResult' => true,
            ),
        );
    }
}
