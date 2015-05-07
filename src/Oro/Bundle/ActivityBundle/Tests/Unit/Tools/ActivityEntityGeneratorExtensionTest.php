<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Tools;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityBundle\Tools\ActivityEntityGeneratorExtension;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ActivityEntityGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $groupingConfigProvider;

    /** @var ActivityEntityGeneratorExtension */
    protected $extension;

    public function setUp()
    {
        $this->groupingConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ActivityEntityGeneratorExtension($this->groupingConfigProvider);
    }

    public function testSupports()
    {
        $schema = [
            'class' => 'Test\Entity'
        ];

        $config = new Config(new EntityConfigId('grouping', 'Test\Entity'));
        $config->set('groups', [ActivityScope::GROUP_ACTIVITY]);

        $this->groupingConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->will($this->returnValue(true));
        $this->groupingConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity')
            ->will($this->returnValue($config));

        $this->assertTrue(
            $this->extension->supports($schema)
        );
    }

    public function testSupportsForNotConfigurableEntity()
    {
        $this->groupingConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->will($this->returnValue(false));
        $this->groupingConfigProvider->expects($this->never())
            ->method('getConfig');

        $this->assertFalse(
            $this->extension->supports(['class' => 'Test\Entity'])
        );
    }

    public function testSupportsForEntityNotIncludedInAnyGroup()
    {
        $config = new Config(new EntityConfigId('grouping', 'Test\Entity'));

        $this->groupingConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->will($this->returnValue(true));
        $this->groupingConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity')
            ->will($this->returnValue($config));

        $this->assertFalse(
            $this->extension->supports(['class' => 'Test\Entity'])
        );
    }

    public function testSupportsForNotActivityEntity()
    {
        $config = new Config(new EntityConfigId('grouping', 'Test\Entity'));
        $config->set('groups', ['another_group']);

        $this->groupingConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->will($this->returnValue(true));
        $this->groupingConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity')
            ->will($this->returnValue($config));

        $this->assertFalse(
            $this->extension->supports(['class' => 'Test\Entity'])
        );
    }

    public function testGenerate()
    {
        $schema = [
            'relationData' => [
                [
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        ExtendHelper::buildAssociationName('Test\TargetEntity1', ActivityScope::ASSOCIATION_KIND),
                        'manyToMany'
                    ),
                    'target_entity' => 'Test\TargetEntity1',
                ],
                [
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        ExtendHelper::buildAssociationName('Test\TargetEntity2', ActivityScope::ASSOCIATION_KIND),
                        'manyToMany'
                    ),
                    'target_entity' => 'Test\TargetEntity2',
                ],
                [ // should be ignored because field type is not manyToMany
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        ExtendHelper::buildAssociationName('Test\TargetEntity3', ActivityScope::ASSOCIATION_KIND),
                        'manyToOne'
                    ),
                    'target_entity' => 'Test\TargetEntity3'
                ],
                [ // should be ignored because field name is not match association naming conventions
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        'testField',
                        'manyToMany'
                    ),
                    'target_entity' => 'Test\TargetEntity4'
                ],
            ],
        ];

        $class = PhpClass::create('Test\Entity');

        $this->extension->generate($schema, $class);
        $strategy     = new DefaultGeneratorStrategy();
        $classBody    = $strategy->generate($class);
        $expectedBody = file_get_contents(__DIR__ . '/Fixtures/generationResult.txt');

        $this->assertEquals(trim($expectedBody), $classBody);
    }
}
