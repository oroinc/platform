<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigDefinitionImmutableBag;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class ConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager */
    protected $manager;

    /** @var ConfigDefinitionImmutableBag */
    protected $bag;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $globalScopeManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $userScopeManager;

    /**
     * @var array
     */
    protected $settings = array(
        'oro_user' => array(
            'greeting' => array(
                'value' => true,
                'type'  => 'boolean',
            ),
            'level'    => array(
                'value' => 20,
                'type'  => 'scalar',
            )
        ),
        'oro_test' => array(
            'anysetting' => array(
                'value' => 'anyvalue',
                'type'  => 'scalar',
            ),
        ),
    );

    public function setUp()
    {
        $this->bag = new ConfigDefinitionImmutableBag($this->settings);
        $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager = new ConfigManager($this->bag, $this->dispatcher);

        $this->globalScopeManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\GlobalScopeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userScopeManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\GlobalScopeManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager->addManager('global', $this->globalScopeManager);
        $this->manager->addManager('user', $this->userScopeManager);
        $this->manager->setScopeName('user');
    }

    public function testSet()
    {
        $name = 'testName';
        $value = 'testValue';
        $this->userScopeManager->expects($this->once())
            ->method('set')
            ->with($name, $value);

        $this->manager->set($name, $value);
    }

    public function testReset()
    {
        $name = 'testName.testValue';
        $this->userScopeManager->expects($this->once())
            ->method('reset')
            ->with($name);

        $this->manager->reset($name);
    }

    public function testFlush()
    {
        $this->userScopeManager->expects($this->once())
            ->method('flush');

        $this->manager->flush();
    }

    public function testSave()
    {
        $data = [];

        $this->userScopeManager->expects($this->once())
            ->method('save')
            ->with($data)
            ->willReturn([[], []]);
        $this->dispatcher->expects($this->once())
            ->method('dispatch');
        $this->userScopeManager->expects($this->once())
            ->method('reload');

        $this->manager->save($data);
    }

    public function testCalculateChangeSet()
    {
        $data = [];
        $this->userScopeManager->expects($this->once())
            ->method('calculateChangeSet')
            ->with($data);

        $this->manager->calculateChangeSet($data);
    }

    public function testLoadStoredSettings()
    {
        $entity = 'test';
        $entityId = 1;
        $this->userScopeManager->expects($this->once())
            ->method('loadStoredSettings')
            ->with($entity, $entityId);

        $this->manager->loadStoredSettings($entity, $entityId);
    }

    public function testReload()
    {
        $this->userScopeManager->expects($this->once())
            ->method('reload');

        $this->manager->reload();
    }

    /**
     * @param bool  $full
     * @param array $expectedResult
     *
     * @dataProvider getFromParentParamProvider
     */
    public function testGetFromParentScope($parameterName, $full, $expectedResult)
    {
        $this->userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->willReturn(null);

        $this->globalScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->willReturnCallback(function($name, $full) {
                if ($name === 'oro_test.someArrayValue') {
                    $value = ['foo' => 'bar'];
                    if ($full) {
                        $value = [
                            'scope' => 'global',
                            'value' => ['foo' => 'bar'],
                            'use_parent_scope_value' => false
                        ];
                    }
                } else {
                    $value = 1;
                    if ($full) {
                        $value = ['scope' => 'global', 'value' => 1];
                    }
                }

                return $value;
            });

        $this->assertEquals(
            $expectedResult,
            $this->manager->get($parameterName, false, $full)
        );
    }

    public function getFromParentParamProvider()
    {
        return [
            [
                'parameterName' => 'oro_test.someValue',
                'full' => false,
                'expectedResult' => 1,
            ],
            [
                'parameterName' => 'oro_test.someValue',
                'full' => true,
                'expectedResult' => ['scope' => 'global', 'value' => 1, 'use_parent_scope_value' => true],
            ],
            [
                'parameterName' => 'oro_test.someArrayValue',
                'full' => true,
                'expectedResult' => [
                    'scope' => 'global',
                    'value' => ['foo' => 'bar'],
                    'use_parent_scope_value' => true
                ]
            ],
            [
                'parameterName' => 'oro_test.someArrayValue',
                'full' => false,
                'expectedResult' => ['foo' => 'bar']
            ]
        ];
    }

    public function testGet()
    {
        $parameterName = 'oro_test.someValue';

        $this->userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->willReturn(['value' => 2]);

        $this->globalScopeManager->expects($this->never())
            ->method('getSettingValue');
        $this->globalScopeManager->expects($this->never())
            ->method('getScopedEntityName');

        $this->assertEquals(
            ['value' => 2],
            $this->manager->get($parameterName)
        );
    }

    public function testGetDefaultSettings()
    {
        $parameterName = 'oro_test.anysetting';

        $this->userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->willReturn(null);
        $this->userScopeManager->expects($this->never())
            ->method('getScopedEntityName');

        $this->globalScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->willReturn(null);
        $this->globalScopeManager->expects($this->never())
            ->method('getScopedEntityName');

        $this->assertEquals(
            'anyvalue',
            $this->manager->get($parameterName)
        );
    }
}
