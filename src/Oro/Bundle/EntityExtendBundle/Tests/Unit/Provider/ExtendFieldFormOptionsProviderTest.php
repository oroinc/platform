<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormOptionsProvider;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormOptionsProviderInterface;

class ExtendFieldFormOptionsProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetOptionsReturnsEmptyArrayWhenNoProviders(): void
    {
        $className = \stdClass::class;
        $fieldName = 'sampleField';

        $provider = new ExtendFieldFormOptionsProvider([]);
        self::assertEquals([], $provider->getOptions($className, $fieldName));
    }

    public function testGetOptionsReturnsEmptyArrayWhenProvidersReturnEmptyArray(): void
    {
        $className = \stdClass::class;
        $fieldName = 'sampleField';

        $provider1 = $this->createMock(ExtendFieldFormOptionsProviderInterface::class);
        $provider1->expects(self::once())
            ->method('getOptions')
            ->with($className, $fieldName)
            ->willReturn([]);

        $provider2 = $this->createMock(ExtendFieldFormOptionsProviderInterface::class);
        $provider2->expects(self::once())
            ->method('getOptions')
            ->with($className, $fieldName)
            ->willReturn([]);

        $provider = new ExtendFieldFormOptionsProvider([$provider1, $provider2]);
        self::assertEquals([], $provider->getOptions($className, $fieldName));
    }

    public function testGetOptionsReturnsNotEmptyArrayWhenProvidersReturnNotEmptyArray(): void
    {
        $className = \stdClass::class;
        $fieldName = 'sampleField';

        $provider1 = $this->createMock(ExtendFieldFormOptionsProviderInterface::class);
        $provider1->expects(self::once())
            ->method('getOptions')
            ->with($className, $fieldName)
            ->willReturn(['sample_key1' => 'sample_value1']);

        $provider2 = $this->createMock(ExtendFieldFormOptionsProviderInterface::class);
        $provider2->expects(self::once())
            ->method('getOptions')
            ->with($className, $fieldName)
            ->willReturn(['sample_key2' => 'sample_value2']);

        $provider = new ExtendFieldFormOptionsProvider([$provider1, $provider2]);
        self::assertEquals(
            ['sample_key1' => 'sample_value1', 'sample_key2' => 'sample_value2'],
            $provider->getOptions($className, $fieldName)
        );
    }

    public function testGetOptionsReturnsMergedArrayWhenProvidersReturnSameKeys(): void
    {
        $className = \stdClass::class;
        $fieldName = 'sampleField';

        $provider1 = $this->createMock(ExtendFieldFormOptionsProviderInterface::class);
        $provider1->expects(self::once())
            ->method('getOptions')
            ->with($className, $fieldName)
            ->willReturn(['sample_key1' => ['sample_key1_1' => 'sample_value_1_1', 'same_key' => 'same_value']]);

        $provider2 = $this->createMock(ExtendFieldFormOptionsProviderInterface::class);
        $provider2->expects(self::once())
            ->method('getOptions')
            ->with($className, $fieldName)
            ->willReturn(['sample_key1' => ['sample_key2_1' => 'sample_value_2_1', 'same_key' => 'new_value']]);

        $provider = new ExtendFieldFormOptionsProvider([$provider1, $provider2]);
        self::assertEquals(
            [
                'sample_key1' => [
                    'sample_key1_1' => 'sample_value_1_1',
                    'sample_key2_1' => 'sample_value_2_1',
                    'same_key' => 'new_value',
                ],
            ],
            $provider->getOptions($className, $fieldName)
        );
    }
}
