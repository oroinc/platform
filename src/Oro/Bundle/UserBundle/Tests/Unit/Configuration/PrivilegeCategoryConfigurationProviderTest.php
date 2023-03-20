<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Configuration;

use Oro\Bundle\UserBundle\Configuration\PrivilegeCategoryConfigurationProvider;
use Oro\Bundle\UserBundle\Tests\Unit\Configuration\Fixture\Bundle\TestBundle1\TestBundle1;
use Oro\Bundle\UserBundle\Tests\Unit\Configuration\Fixture\Bundle\TestBundle2\TestBundle2;
use Oro\Bundle\UserBundle\Tests\Unit\Configuration\Fixture\Bundle\TestBundle3\TestBundle3;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;

class PrivilegeCategoryConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var string */
    private $cacheFile;

    /** @var PrivilegeCategoryConfigurationProvider */
    private $configurationProvider;

    protected function setUp(): void
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        $bundle3 = new TestBundle3();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle2->getName() => get_class($bundle2),
                $bundle1->getName() => get_class($bundle1),
                $bundle3->getName() => get_class($bundle3)
            ]);

        $this->cacheFile = $this->getTempFile('PrivilegeCategoryConfigProvider');

        $this->configurationProvider = new PrivilegeCategoryConfigurationProvider($this->cacheFile, false);
    }

    public function testGetConfigurationWithCache()
    {
        $cachedCategories = [
            'category1' => [
                ['label' => 'category1.label', 'tab' => true, 'priority' => 1]
            ]
        ];
        file_put_contents($this->cacheFile, sprintf('<?php return %s;', var_export($cachedCategories, true)));

        self::assertSame($cachedCategories, $this->configurationProvider->getCategories());
    }

    public function testGetConfigurationWithoutCache()
    {
        $this->configurationProvider->clearCache();

        $categories = $this->configurationProvider->getCategories();

        $expectedConfig = [
            'category8' => ['label' => 'category8.label', 'tab' => false, 'priority' => 0],
            'category7' => ['label' => 'category7.label', 'tab' => false, 'priority' => 1],
            'category6' => ['label' => 'category6.label', 'tab' => true, 'priority' => 5],
            'category1' => ['label' => 'category1.label', 'tab' => true, 'priority' => 10],
            'category5' => ['label' => 'category5.label', 'tab' => true, 'priority' => 15],
            'category4' => ['label' => 'category4.label', 'tab' => false, 'priority' => 25],
            'category3' => ['label' => 'category3.label.updated', 'tab' => true, 'priority' => 30]
        ];

        self::assertSame($expectedConfig, $categories);
    }
}
