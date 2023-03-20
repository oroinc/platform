<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Configuration;

use Oro\Bundle\LocaleBundle\Configuration\NameFormatConfigurationProvider;
use Oro\Bundle\LocaleBundle\Tests\Unit\Configuration\Fixtures\TestBundle1\TestBundle1;
use Oro\Bundle\LocaleBundle\Tests\Unit\Configuration\Fixtures\TestBundle2\TestBundle2;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;

class NameFormatConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private NameFormatConfigurationProvider $configurationProvider;

    protected function setUp(): void
    {
        $cacheFile = $this->getTempFile('NameFormatConfigurationProvider');

        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);

        $this->configurationProvider = new NameFormatConfigurationProvider($cacheFile, false);
    }

    public function testGetConfiguration()
    {
        $this->assertEquals(
            [
                'en' => '%prefix% %first_name% %middle_name% %last_name% %suffix%',
                'ru' => '%last_name% %first_name% %middle_name%'
            ],
            $this->configurationProvider->getConfiguration()
        );
    }
}
