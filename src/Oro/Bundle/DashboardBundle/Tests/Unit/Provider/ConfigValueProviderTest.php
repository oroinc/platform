<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueProvider;
use Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Provider\TestConverter;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\TestCase;

class ConfigValueProviderTest extends TestCase
{
    private ConfigValueProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $converter = new TestConverter();

        $container = TestContainerBuilder::create()
            ->add('test_form_type', $converter)
            ->getContainer($this);

        $this->provider = new ConfigValueProvider($container);
    }

    public function testGetConvertedValueUnsupported(): void
    {
        $value = 'test';
        self::assertSame($value, $this->provider->getConvertedValue([], 'unsupported', $value));
    }

    public function testGetConvertedValue(): void
    {
        $value = 'test value';
        self::assertSame('test value', $this->provider->getConvertedValue([], 'test_form_type', $value));
    }

    public function testGetViewValueUnsupported(): void
    {
        $value = 'test';
        self::assertSame($value, $this->provider->getViewValue('unsupported', $value));
    }

    public function testGetViewValue(): void
    {
        $value = 'test';
        self::assertSame('test view value', $this->provider->getViewValue('test_form_type', $value));
    }

    public function testGetFormValueUnsupported(): void
    {
        $value = 'test';
        $this->assertSame($value, $this->provider->getFormValue('unsupported', [], $value));
    }

    public function testGetFormValue(): void
    {
        $value = 'test';
        $convertedValue = 'converted';
        self::assertSame(
            $convertedValue,
            $this->provider->getFormValue('test_form_type', ['value' => $convertedValue], $value)
        );
    }
}
