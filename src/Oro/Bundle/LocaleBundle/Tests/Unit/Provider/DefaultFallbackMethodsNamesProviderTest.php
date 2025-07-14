<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Oro\Bundle\LocaleBundle\Provider\DefaultFallbackMethodsNamesProvider;
use PHPUnit\Framework\TestCase;

class DefaultFallbackMethodsNamesProviderTest extends TestCase
{
    private Inflector $inflector;
    private DefaultFallbackMethodsNamesProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->inflector = InflectorFactory::create()->build();

        $this->provider = new DefaultFallbackMethodsNamesProvider($this->inflector);
    }

    /**
     * @dataProvider fieldNameDataProvider
     */
    public function testGetGetterMethodName(string $fieldName): void
    {
        self::assertEquals('getSampleField', $this->provider->getGetterMethodName($fieldName));
    }

    public function fieldNameDataProvider(): array
    {
        return [
            ['sampleFields'],
            ['sample_fields'],
            ['sample_Fields'],
            ['sampleField'],
            ['sample_field'],
            ['sample_Field'],
        ];
    }

    /**
     * @dataProvider fieldNameDataProvider
     */
    public function testGetDefaultGetterMethodName(string $fieldName): void
    {
        self::assertEquals('getDefaultSampleField', $this->provider->getDefaultGetterMethodName($fieldName));
    }

    /**
     * @dataProvider fieldNameDataProvider
     */
    public function testGetDefaultSetterMethodName(string $fieldName): void
    {
        self::assertEquals('setDefaultSampleField', $this->provider->getDefaultSetterMethodName($fieldName));
    }
}
