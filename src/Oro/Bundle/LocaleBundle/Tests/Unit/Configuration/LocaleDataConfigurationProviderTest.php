<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Configuration;

use Oro\Bundle\LocaleBundle\Configuration\LocaleDataConfigurationProvider;
use Oro\Bundle\LocaleBundle\Tests\Unit\Configuration\Fixtures\TestBundle1\TestBundle1;
use Oro\Bundle\LocaleBundle\Tests\Unit\Configuration\Fixtures\TestBundle2\TestBundle2;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;

class LocaleDataConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private LocaleDataConfigurationProvider $configurationProvider;

    protected function setUp(): void
    {
        $cacheFile = $this->getTempFile('LocaleDataConfigurationProvider');

        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);

        $this->configurationProvider = new LocaleDataConfigurationProvider($cacheFile, false);
    }

    public function testGetConfiguration()
    {
        $this->assertEquals(
            [
                'US' => [
                    'currency_code'  => 'USD',
                    'phone_prefix'   => '1',
                    'default_locale' => 'en_US'
                ],
                'RU' => [
                    'currency_code'  => 'RUB',
                    'phone_prefix'   => '7',
                    'default_locale' => 'ru_RU'
                ]
            ],
            $this->configurationProvider->getConfiguration()
        );
    }
}
