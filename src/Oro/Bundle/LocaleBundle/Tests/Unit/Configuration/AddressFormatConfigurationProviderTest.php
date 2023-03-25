<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Configuration;

use Oro\Bundle\LocaleBundle\Configuration\AddressFormatConfigurationProvider;
use Oro\Bundle\LocaleBundle\Tests\Unit\Configuration\Fixtures\TestBundle1\TestBundle1;
use Oro\Bundle\LocaleBundle\Tests\Unit\Configuration\Fixtures\TestBundle2\TestBundle2;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;

class AddressFormatConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private AddressFormatConfigurationProvider $configurationProvider;

    protected function setUp(): void
    {
        $cacheFile = $this->getTempFile('AddressFormatConfigurationProvider');

        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);

        $this->configurationProvider = new AddressFormatConfigurationProvider($cacheFile, false);
    }

    public function testGetConfiguration()
    {
        $this->assertEquals(
            [
                'US' => [
                    'format'           => '%name%\n%organization%\n%street%',
                    'require'          => ['street', 'city', 'region', 'postal_code'],
                    'zip_name_type'    => 'zip',
                    'region_name_type' => 'region',
                    'latin_format'     => '%name%\n%organization%\n%street%\n%CITY%\n%COUNTRY%',
                    'direction'        => 'ltr',
                    'postprefix'       => null,
                    'has_disputed'     => false,
                    'format_charset'   => 'UTF-8'
                ],
                'CN' => [
                    'format'           => '%postal_code%\n%COUNTRY%\n%REGION%%city%\n%street%',
                    'require'          => ['city', 'postal_code'],
                    'zip_name_type'    => 'postal',
                    'region_name_type' => 'province',
                    'latin_format'     => '%street%, %city%\n%REGION%, %COUNTRY% %postal_code%',
                    'direction'        => 'ltr',
                    'postprefix'       => null,
                    'has_disputed'     => true,
                    'format_charset'   => 'GB2312'
                ]
            ],
            $this->configurationProvider->getConfiguration()
        );
    }
}
