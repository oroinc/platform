<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationExtendConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class AssociationExtendConfigDumperExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $assocHelperMock;

    public function setUp()
    {
        $this->assocHelperMock = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Tools\AssociationBuildHelper')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test supports method
     */
    public function testSupports()
    {
        $extension = $this->getExtensionMock(['getTargetEntities']);

        $extension->expects($this->at(0))
            ->method('getTargetEntities')
            ->will($this->returnValue(['Test\Entity']));

        $extension->expects($this->at(1))
            ->method('getTargetEntities')
            ->will($this->returnValue([]));

        $this->assertTrue(
            $extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE),
            'pre update supported'
        );

        $this->assertFalse(
            $extension->supports(ExtendConfigDumper::ACTION_POST_UPDATE),
            'post update not supported'
        );

        $this->assertFalse(
            $extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE),
            'pre update with no target entities not supported'
        );
    }

    /**
     * Test preUpdate method
     */
    public function testPreUpdate()
    {
        $extension = $this->getExtensionMock(
            [
                'getAssociationEntityClass',
                'getTargetEntities',
            ]
        );

        $assocClassName = 'Test\AssocEntity';
        $configs        = [];

        $extension->expects($this->once())
            ->method('getAssociationEntityClass')
            ->will($this->returnValue($assocClassName));

        $extension->expects($this->once())
            ->method('getTargetEntities')
            ->will($this->returnValue(['Test\Entity', 'Test\Entity2']));

        $this->assocHelperMock->expects($this->at(0))
            ->method('createManyToOneAssociation')
            ->with($assocClassName, 'Test\Entity');

        $this->assocHelperMock->expects($this->at(1))
            ->method('createManyToOneAssociation')
            ->with($assocClassName, 'Test\Entity2');

        $extension->preUpdate($configs);
    }

    /**
     * Test target entity matching
     */
    public function testGetTargetEntities()
    {
        $extension = $this->getExtensionMock(
            [
                'getAssociationScope',
            ]
        );

        $config1 = new Config(new EntityConfigId('test_scope', 'Test\Entity'));
        $config1->set('enabled', true);

        $config2 = new Config(new EntityConfigId('test_scope', 'Test\Entity\Another'));

        $configs = [$config1, $config2];

        $extension->expects($this->once())
            ->method('getAssociationScope')
            ->will($this->returnValue('test_scope'));

        $this->assocHelperMock->expects($this->once())
            ->method('getScopeConfigs')
            ->with('test_scope')
            ->will($this->returnValue($configs));

        $targetEntities = $this->callProtectedMethod($extension, 'getTargetEntities');
        $this->assertSame($targetEntities, ['Test\Entity']);
    }

    /**
     * @param  mixed  $obj
     * @param  string $methodName
     * @param  array  $args
     *
     * @return mixed
     */
    public static function callProtectedMethod($obj, $methodName, array $args = [])
    {
        $class  = new \ReflectionClass($obj);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }

    /**
     * @param array $methods
     *
     * @return AssociationExtendConfigDumperExtension|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getExtensionMock(array $methods = [])
    {
        $extension = $this->getMockForAbstractClass(
            'Oro\Bundle\EntityExtendBundle\Tools\AssociationExtendConfigDumperExtension',
            [$this->assocHelperMock],
            '',
            true,
            true,
            true,
            $methods
        );

        return $extension;
    }
}
