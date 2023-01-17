<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormOptionsDefaultProvider;

class ExtendFieldFormOptionsDefaultProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigManager;

    /** @var ExtendFieldFormOptionsDefaultProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->entityConfigManager = $this->createMock(EntityConfigManager::class);

        $this->provider = new ExtendFieldFormOptionsDefaultProvider($this->entityConfigManager);
    }

    public function testGetOptions(): void
    {
        $className = \stdClass::class;
        $fieldName = 'sampleField';
        $configOptions = ['label' => 'Sample label'];
        $entityFieldConfig = new Config(new FieldConfigId('entity', $className, $fieldName, 'string'), $configOptions);

        $this->entityConfigManager->expects(self::once())
            ->method('getFieldConfig')
            ->willReturn($entityFieldConfig);

        self::assertEquals(
            [
                'label' => $configOptions['label'],
                'required' => false,
                'block' => 'general',
            ],
            $this->provider->getOptions($className, $fieldName)
        );
    }
}
