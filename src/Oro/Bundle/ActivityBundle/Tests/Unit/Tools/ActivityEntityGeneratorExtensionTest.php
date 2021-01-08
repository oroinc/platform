<?php
declare(strict_types=1);

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Tools;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityBundle\Tools\ActivityEntityGeneratorExtension;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\PhpUtils\ClassGenerator;
use PHPUnit\Framework\MockObject\MockObject;

class ActivityEntityGeneratorExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var MockObject|ConfigProvider */
    protected ConfigProvider $groupingConfigProvider;

    protected ActivityEntityGeneratorExtension $extension;

    protected function setUp(): void
    {
        $this->groupingConfigProvider = $this->createMock(ConfigProvider::class);

        $this->extension = new ActivityEntityGeneratorExtension($this->groupingConfigProvider);
    }

    public function testSupports()
    {
        $schema = [
            'class' => 'Test\Entity'
        ];

        $config = new Config(new EntityConfigId('grouping', 'Test\Entity'));
        $config->set('groups', [ActivityScope::GROUP_ACTIVITY]);

        $this->groupingConfigProvider->expects(static::once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->willReturn(true);
        $this->groupingConfigProvider->expects(static::once())
            ->method('getConfig')
            ->with('Test\Entity')
            ->willReturn($config);

        static::assertTrue($this->extension->supports($schema));
    }

    public function testSupportsForNotConfigurableEntity()
    {
        $this->groupingConfigProvider->expects(static::once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->willReturn(false);
        $this->groupingConfigProvider->expects(static::never())
            ->method('getConfig');

        static::assertFalse($this->extension->supports(['class' => 'Test\Entity']));
    }

    public function testSupportsForEntityNotIncludedInAnyGroup()
    {
        $config = new Config(new EntityConfigId('grouping', 'Test\Entity'));

        $this->groupingConfigProvider->expects(static::once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->willReturn(true);
        $this->groupingConfigProvider->expects(static::once())
            ->method('getConfig')
            ->with('Test\Entity')
            ->willReturn($config);

        static::assertFalse($this->extension->supports(['class' => 'Test\Entity']));
    }

    public function testSupportsForNotActivityEntity()
    {
        $config = new Config(new EntityConfigId('grouping', 'Test\Entity'));
        $config->set('groups', ['another_group']);

        $this->groupingConfigProvider->expects(static::once())
            ->method('hasConfig')
            ->with('Test\Entity')
            ->willReturn(true);
        $this->groupingConfigProvider->expects(static::once())
            ->method('getConfig')
            ->with('Test\Entity')
            ->willReturn($config);

        static::assertFalse($this->extension->supports(['class' => 'Test\Entity']));
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
                    'state' => 'Active'
                ],
                [
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        ExtendHelper::buildAssociationName('Test\TargetEntity2', ActivityScope::ASSOCIATION_KIND),
                        'manyToMany'
                    ),
                    'target_entity' => 'Test\TargetEntity2',
                    'state' => 'Active'
                ],
                [ // should be ignored because field type is not manyToMany
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        ExtendHelper::buildAssociationName('Test\TargetEntity3', ActivityScope::ASSOCIATION_KIND),
                        'manyToOne'
                    ),
                    'target_entity' => 'Test\TargetEntity3',
                    'state' => 'Active'

                ],
                [ // should be ignored because field name is not match association naming conventions
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        'testField',
                        'manyToMany'
                    ),
                    'target_entity' => 'Test\TargetEntity4',
                    'state' => 'Active'
                ],
            ],
        ];

        $class = new ClassGenerator('Test\Entity');
        $class->addMethod('__construct')
            ->addComment('Making sure that existing methods are not removed by the code generation');
        $class->addMethod('someExistingMethod')
            ->addComment('Making sure that existing methods are not removed by the code generation');

        $this->extension->generate($schema, $class);
        $expectedBody = \file_get_contents(__DIR__ . '/Fixtures/generationResult.txt');

        static::assertEquals($expectedBody, $class->print());
    }
}
