<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Configuration;

use Oro\Bundle\LocaleBundle\Configuration\AddressFormatConfigurationProvider;
use Oro\Bundle\LocaleBundle\Configuration\LocaleConfigurationProvider;
use Oro\Bundle\LocaleBundle\Configuration\LocaleDataConfigurationProvider;
use Oro\Bundle\LocaleBundle\Configuration\NameFormatConfigurationProvider;

class LocaleConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var NameFormatConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $nameFormatConfigProvider;

    /** @var AddressFormatConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $addressFormatConfigProvider;

    /** @var LocaleDataConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $localeDataConfigProvider;

    /** @var LocaleConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    protected function setUp(): void
    {
        $this->nameFormatConfigProvider = $this->createMock(NameFormatConfigurationProvider::class);
        $this->addressFormatConfigProvider = $this->createMock(AddressFormatConfigurationProvider::class);
        $this->localeDataConfigProvider = $this->createMock(LocaleDataConfigurationProvider::class);

        $this->configProvider = new LocaleConfigurationProvider(
            $this->nameFormatConfigProvider,
            $this->addressFormatConfigProvider,
            $this->localeDataConfigProvider
        );
    }

    public function testGetNameFormats()
    {
        $config = ['en' => '%first_name% %last_name%'];

        $this->nameFormatConfigProvider->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($config);

        self::assertEquals(
            $config,
            $this->configProvider->getNameFormats()
        );
    }

    public function testGetAddressFormats()
    {
        $config = ['US' => ['format' => '%name%\n%organization%']];

        $this->addressFormatConfigProvider->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($config);

        self::assertEquals(
            $config,
            $this->configProvider->getAddressFormats()
        );
    }

    public function testGetLocaleData()
    {
        $config = ['US' => ['default_locale' => 'en_US']];

        $this->localeDataConfigProvider->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($config);

        self::assertEquals(
            $config,
            $this->configProvider->getLocaleData()
        );
    }
}
