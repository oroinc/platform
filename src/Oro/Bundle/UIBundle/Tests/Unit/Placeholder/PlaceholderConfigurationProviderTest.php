<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Placeholder;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderConfigurationProvider;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\BarBundle\BarBundle;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\BazBundle\BazBundle;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\FooBundle\FooBundle;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;

class PlaceholderConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private PlaceholderConfigurationProvider $configurationProvider;

    protected function setUp(): void
    {
        $cacheFile = $this->getTempFile('PlaceholderConfigurationProvider');

        $this->configurationProvider = new PlaceholderConfigurationProvider($cacheFile, false);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetConfiguration()
    {
        $bundle1 = new BarBundle();
        $bundle2 = new FooBundle();
        $bundle3 = new BazBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2),
                $bundle3->getName() => get_class($bundle3)
            ]);

        $placeholders = [
            'test_block'       => [
                'items' => ['item6', 'item7', 'item2', 'item3', 'new_item', 'item4', 'item5', 'new_empty_item']
            ],
            'test_merge_block' => [
                'items' => ['item1']
            ],
            'empty_block'      => null
        ];
        $items = [
            'item1'                      => [
                'template' => '@@Test/test.html.twig',
            ],
            'item2'                      => [
                'action' => 'Oro\Bundle\TestBundle\Controller\TestController::test2Action',
            ],
            'item3'                      => [
                'action' => 'Oro\Bundle\TestBundle\Controller\TestController::test3Action',
            ],
            'item4'                      => [
                'action' => 'Oro\Bundle\TestBundle\Controller\TestController::test4Action',
            ],
            'item5'                      => [
                'action' => 'Oro\Bundle\TestBundle\Controller\TestController::test5Action',
            ],
            'item6'                      => [
                'action' => 'Oro\Bundle\TestBundle\Controller\TestController::test6Action',
            ],
            'item7'                      => [
                'action' => 'Oro\Bundle\TestBundle\Controller\TestController::test7Action',
            ],
            'new_item'                   => [
                'template' => 'test_template',
            ],
            'new_applicable_string_item' => [
                'template'   => 'test_template',
                'applicable' => 'test_condition'
            ],
            'new_applicable_array_item'  => [
                'template'   => 'test_template',
                'applicable' => ['test_condition1', 'test_condition2']
            ]
        ];

        foreach ($placeholders as $placeholderName => $placeholder) {
            self::assertSame(
                $placeholder['items'] ?? null,
                $this->configurationProvider->getPlaceholderItems($placeholderName),
                sprintf('Placeholder Items for "%s"', $placeholderName)
            );
        }

        foreach ($items as $itemName => $itemConfig) {
            self::assertSame(
                $itemConfig,
                $this->configurationProvider->getItemConfiguration($itemName),
                sprintf('Item "%s"', $itemName)
            );
        }
    }
}
