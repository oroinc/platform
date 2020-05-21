<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EnumVirtualFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumVirtualFieldProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EnumVirtualFieldProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new EnumVirtualFieldProvider($this->configManager);
    }

    public function testGetVirtualFields()
    {
        $className = 'Test\Entity';
        $this->initialize($className);

        $this->assertEquals(
            ['enumField', 'multiEnumField'],
            $this->provider->getVirtualFields($className)
        );
    }

    public function testIsVirtualField()
    {
        $className = 'Test\Entity';
        $this->initialize($className);

        $this->assertTrue(
            $this->provider->isVirtualField($className, 'enumField')
        );
        $this->assertTrue(
            $this->provider->isVirtualField($className, 'multiEnumField')
        );
        $this->assertFalse(
            $this->provider->isVirtualField($className, 'nonConfigurableField')
        );
    }

    public function testGetVirtualFieldQueryForEnum()
    {
        $className = 'Test\Entity';
        $this->initialize($className);

        $this->assertEquals(
            [
                'select' => [
                    'expr'         => 'target.targetField',
                    'return_type'  => 'enum',
                    'filter_by_id' => true
                ],
                'join'   => [
                    'left' => [
                        [
                            'join'  => 'entity.enumField',
                            'alias' => 'target'
                        ]
                    ]
                ]
            ],
            $this->provider->getVirtualFieldQuery($className, 'enumField')
        );
    }

    public function testGetVirtualFieldQueryForMultiEnum()
    {
        $className = 'Test\Entity';
        $this->initialize($className);

        $this->assertEquals(
            [
                'select' => [
                    'expr'         => 'entity.' . ExtendHelper::getMultiEnumSnapshotFieldName('multiEnumField'),
                    'return_type'  => 'multiEnum',
                    'filter_by_id' => true
                ]
            ],
            $this->provider->getVirtualFieldQuery($className, 'multiEnumField')
        );
    }

    private function initialize($className)
    {
        $enumFieldConfig = new Config(new FieldConfigId('extend', $className, 'enumField', 'enum'));
        $enumFieldConfig->set('target_field', 'targetField');
        $multiEnumFieldConfig = new Config(new FieldConfigId('extend', $className, 'multiEnumField', 'multiEnum'));

        $this->configManager->expects($this->once())
            ->method('getIds')
            ->with('extend', $className)
            ->willReturn([
                $enumFieldConfig->getId(),
                $multiEnumFieldConfig->getId()
            ]);
        $this->configManager->expects($this->exactly(2))
            ->method('getFieldConfig')
            ->willReturnMap([
                ['extend', $className, 'enumField', $enumFieldConfig],
                ['extend', $className, 'multiEnumField', $multiEnumFieldConfig]
            ]);
    }
}
