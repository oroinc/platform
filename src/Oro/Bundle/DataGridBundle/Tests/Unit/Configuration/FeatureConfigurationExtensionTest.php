<?php

declare(strict_types=1);

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Configuration;

use Oro\Bundle\DataGridBundle\Configuration\FeatureConfigurationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class FeatureConfigurationExtensionTest extends TestCase
{
    private FeatureConfigurationExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->extension = new FeatureConfigurationExtension();
    }

    private function processConfiguration(array $configs): array
    {
        $treeBuilder = new TreeBuilder('features');
        $this->extension->extendConfigurationTree(
            $treeBuilder->getRootNode()->useAttributeAsKey('name')->prototype('array')->children()
        );

        return (new Processor())->process($treeBuilder->buildTree(), $configs);
    }

    public function testDatagrids(): void
    {
        self::assertSame(
            ['feature1' => ['datagrids' => ['test-grid', 'another-grid']]],
            $this->processConfiguration([['feature1' => ['datagrids' => ['test-grid', 'another-grid']]]])
        );
    }

    public function testDatagridsForSeveralFeatures(): void
    {
        self::assertSame(
            [
                'feature1' => ['datagrids' => ['grid1']],
                'feature2' => ['datagrids' => ['grid2']]
            ],
            $this->processConfiguration([[
                'feature1' => ['datagrids' => ['grid1']],
                'feature2' => ['datagrids' => ['grid2']]
            ]])
        );
    }

    public function testDatagridsFromSeveralConfigsAreMerged(): void
    {
        self::assertSame(
            ['feature1' => ['datagrids' => ['grid1', 'grid2']]],
            $this->processConfiguration([
                ['feature1' => ['datagrids' => ['grid1']]],
                ['feature1' => ['datagrids' => ['grid2']]]
            ])
        );
    }

    public function testWithoutDatagrids(): void
    {
        self::assertSame(
            ['feature1' => ['datagrids' => []]],
            $this->processConfiguration([['feature1' => []]])
        );
    }
}
