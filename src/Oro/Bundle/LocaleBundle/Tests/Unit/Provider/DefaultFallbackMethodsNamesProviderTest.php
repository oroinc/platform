<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Oro\Bundle\LocaleBundle\Provider\DefaultFallbackMethodsNamesProvider;

class DefaultFallbackMethodsNamesProviderTest extends \PHPUnit\Framework\TestCase
{
    private Inflector $inflector;

    private DefaultFallbackMethodsNamesProvider $provider;

    protected function setUp(): void
    {
        $this->inflector = InflectorFactory::create()->build();

        $this->provider = new DefaultFallbackMethodsNamesProvider($this->inflector);
    }

    /**
     * @dataProvider fieldNameDataProvider
     *
     * @param string $fieldName
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
     *
     * @param string $fieldName
     */
    public function testGetDefaultGetterMethodName(string $fieldName): void
    {
        self::assertEquals('getDefaultSampleField', $this->provider->getDefaultGetterMethodName($fieldName));
    }

    /**
     * @dataProvider fieldNameDataProvider
     *
     * @param string $fieldName
     */
    public function testGetDefaultSetterMethodName(string $fieldName): void
    {
        self::assertEquals('setDefaultSampleField', $this->provider->getDefaultSetterMethodName($fieldName));
    }
}
