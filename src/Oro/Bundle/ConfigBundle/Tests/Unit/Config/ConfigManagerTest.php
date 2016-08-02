<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\ConfigBundle\Config\ConfigDefinitionImmutableBag;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\ConfigValueBag;
use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ConfigBundle\Event\ConfigGetEvent;

class ConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager */
    protected $manager;

    /** @var ConfigDefinitionImmutableBag */
    protected $bag;

    /** @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject */
    protected $dispatcher;

    /** @var GlobalScopeManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $globalScopeManager;

    /** @var GlobalScopeManager|\PHPUnit_Framework_MockObject_MockObject */
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
            'anysetting'  => array(
                'value' => 'anyvalue',
                'type'  => 'scalar',
            ),
            'emptystring' => array(
                'value' => '',
                'type'  => 'scalar',
            ),
        ),
    );

    public function setUp()
    {
        $this->bag        = new ConfigDefinitionImmutableBag($this->settings);
        $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new ConfigManager(
            'user',
            $this->bag,
            $this->dispatcher,
            new ConfigValueBag()
        );

        $this->globalScopeManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\GlobalScopeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userScopeManager   = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\GlobalScopeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->userScopeManager->expects($this->any())
            ->method('getScopeId')
            ->willReturn(123);

        $this->manager->addManager('user', $this->userScopeManager);
        $this->manager->addManager('global', $this->globalScopeManager);
    }

    public function testSet()
    {
        $name  = 'testName';
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
        $greetingKey = 'oro_user.greeting';
        $changes = [
            $greetingKey => [
                'value'                  => 'updated value',
                'use_parent_scope_value' => false
            ]
        ];

        $this->userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($greetingKey, false)
            ->willReturn('old value');

        $beforeEvent = new ConfigSettingsUpdateEvent($this->manager, $changes);
        $afterEvent  = new ConfigUpdateEvent(
            [
                $greetingKey => ['old' => 'old value', 'new' => 'updated value']
            ]
        );

        $loadEvent = new ConfigGetEvent($this->manager, $greetingKey, 'old value', false);

        $this->userScopeManager->expects($this->once())
            ->method('getChanges')
            ->willReturn($changes);

        $this->userScopeManager->expects($this->once())
            ->method('save')
            ->with($changes)
            ->willReturn(
                [
                    [$greetingKey => 'updated value'],
                    []
                ]
            );

        $this->dispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [ConfigGetEvent::NAME, $loadEvent],
                [ConfigGetEvent::NAME . '.' . $greetingKey, $loadEvent],
                [ConfigSettingsUpdateEvent::BEFORE_SAVE, $beforeEvent],
                [ConfigUpdateEvent::EVENT_NAME, $afterEvent]
            );

        $this->manager->flush();
    }

    public function testSave()
    {

        $greetingKey = 'oro_user.greeting';
        $levelKey = 'oro_user.level';

        $data           = [
            'oro_user___greeting' => [
                'value'                  => 'updated value',
                'use_parent_scope_value' => false
            ],
            'oro_user___unknown'  => [
                'value'                  => 'some value',
                'use_parent_scope_value' => false
            ],
            'oro_user___level'    => [
                'use_parent_scope_value' => true
            ]
        ];
        $normalizedData = [
            $greetingKey => [
                'value'                  => 'updated value',
                'use_parent_scope_value' => false
            ],
            $levelKey    => [
                'use_parent_scope_value' => true
            ]
        ];

        $this->userScopeManager->expects($this->exactly(2))
            ->method('getSettingValue')
            ->willReturnMap(
                [
                    [$greetingKey, false, 'old value'],
                    [$levelKey, false, 2000]
                ]
            );

        $beforeEvent = new ConfigSettingsUpdateEvent($this->manager, $normalizedData);
        $afterEvent  = new ConfigUpdateEvent(
            [
                $greetingKey => ['old' => 'old value', 'new' => 'updated value'],
                $levelKey    => ['old' => 2000, 'new' => 20]
            ]
        );

        $greetingOldValueLoadEvent = new ConfigGetEvent($this->manager, $greetingKey, 'old value', false);
        $levelOldValueLoadEvent = new ConfigGetEvent($this->manager, $levelKey, '2000', false);
        $levelNullValueLoadEvent = new ConfigGetEvent($this->manager, $levelKey, null, false);

        $this->userScopeManager->expects($this->once())
            ->method('save')
            ->with($normalizedData)
            ->willReturn(
                [
                    [$greetingKey => 'updated value'],
                    [$levelKey]
                ]
            );

        $this->dispatcher->expects($this->exactly(8))
            ->method('dispatch')
            ->withConsecutive(
                [ConfigGetEvent::NAME, $greetingOldValueLoadEvent],
                [ConfigGetEvent::NAME . '.' . $greetingKey, $greetingOldValueLoadEvent],
                [ConfigGetEvent::NAME, $levelOldValueLoadEvent],
                [ConfigGetEvent::NAME . '.' . $levelKey, $levelOldValueLoadEvent],
                [ConfigSettingsUpdateEvent::BEFORE_SAVE, $beforeEvent],
                [ConfigGetEvent::NAME, $levelNullValueLoadEvent],
                [ConfigGetEvent::NAME  . '.' . $levelKey, $levelNullValueLoadEvent],
                [ConfigUpdateEvent::EVENT_NAME, $afterEvent]
            );

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

    public function testReload()
    {
        $this->userScopeManager->expects($this->once())
            ->method('reload');

        $this->manager->reload();
    }

    /**
     * @dataProvider getFromParentParamProvider
     * @param string $parameterName
     * @param bool $full
     * @param mixed $expectedResult
     */
    public function testGetFromParentScope($parameterName, $full, $expectedResult)
    {
        $this->userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName, $full)
            ->willReturn(null);

        $this->globalScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName, $full)
            ->willReturnCallback(function ($name, $full) {
                if ($name === 'oro_test.someArrayValue') {
                    $value = ['foo' => 'bar'];
                    if ($full) {
                        $value = [
                            'scope'                  => 'global',
                            'value'                  => ['foo' => 'bar'],
                            'use_parent_scope_value' => false
                        ];
                    }
                } else {
                    $value = 1;
                    if ($full) {
                        $value = ['scope' => 'global', 'value' => 1, 'use_parent_scope_value' => true];
                    }
                }

                return $value;
            });

        $this->assertEquals(
            $expectedResult,
            $this->manager->get($parameterName, false, $full)
        );
    }

    /**
     * @return array
     */
    public function getFromParentParamProvider()
    {
        return [
            [
                'parameterName'  => 'oro_test.someValue',
                'full'           => false,
                'expectedResult' => 1,
            ],
            [
                'parameterName'  => 'oro_test.someValue',
                'full'           => true,
                'expectedResult' => ['scope' => 'global', 'value' => 1, 'use_parent_scope_value' => true],
            ],
            [
                'parameterName'  => 'oro_test.someArrayValue',
                'full'           => true,
                'expectedResult' => [
                    'scope'                  => 'global',
                    'value'                  => ['foo' => 'bar'],
                    'use_parent_scope_value' => true
                ]
            ],
            [
                'parameterName'  => 'oro_test.someArrayValue',
                'full'           => false,
                'expectedResult' => ['foo' => 'bar']
            ]
        ];
    }

    public function testGet()
    {
        $parameterName = 'oro_test.someValue';

        $this->userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName, false)
            ->willReturn(2);

        $this->globalScopeManager->expects($this->never())
            ->method('getSettingValue');

        $this->assertEquals(2, $this->manager->get($parameterName));
    }

    public function testGetDefaultSettings()
    {
        $parameterName = 'oro_test.anysetting';

        $this->userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName, false)
            ->willReturn(null);

        $this->globalScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName, false)
            ->willReturn(null);

        $this->assertEquals(
            'anyvalue',
            $this->manager->get($parameterName)
        );
    }

    public function testGetEmptyValueSettings()
    {
        $parameterName = 'oro_test.emptystring';

        $this->userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName, false)
            ->willReturn('');

        $this->globalScopeManager->expects($this->never())
            ->method('getSettingValue');

        $this->assertEquals('', $this->manager->get($parameterName));
    }

    public function testGetSettingsDefaultsMethodReturnNullForMissing()
    {
        $this->assertEquals(null, $this->manager->getSettingsDefaults('oro_test.null', true));
    }

    public function testGetSettingsDefaultsMethodReturnPlain()
    {
        $this->assertEquals('anyvalue', $this->manager->getSettingsDefaults('oro_test.anysetting'));
    }

    public function testGetSettingsDefaultsMethodReturnFull()
    {
        $this->assertEquals(
            [
                'value' => 'anyvalue',
                'type' => 'scalar',
            ],
            $this->manager->getSettingsDefaults('oro_test.anysetting', true)
        );
    }

    public function testGetMergedWithParentValue()
    {
        $parameterName = 'oro_test.anysetting';

        $this->globalScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName, false)
            ->willReturn(['a' => 1, 'b' => 1, 'c' => 1]);

        $this->userScopeManager->expects($this->once())
            ->method('getSettingValue')
            ->with($parameterName, false)
            ->willReturn(['a' => null, 'b' => 2]);

        $this->assertEquals(
            [
                'a' => null,
                'b' => 2,
                'c' => 1,
            ],
            $this->manager->getMergedWithParentValue([], $parameterName)
        );
    }
}
