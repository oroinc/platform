<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\DependencyInjection\OroFeatureToggleExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OroFeatureToggleExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var OroFeatureToggleExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new OroFeatureToggleExtension();
    }

    public function testGetAlias()
    {
        $this->assertEquals('oro_featuretoggle', $this->extension->getAlias());
    }

    public function testLoadWithoutConfig()
    {
        $container = new ContainerBuilder();

        $this->extension->load([], $container);

        $this->assertEquals(
            [
                new Reference('oro_featuretoggle.configuration.manager'),
                [],
                FeatureChecker::STRATEGY_UNANIMOUS,
                false,
                true
            ],
            $container->getDefinition('oro_featuretoggle.checker.feature_checker')->getArguments()
        );
    }

    public function testLoadWithConfig()
    {
        $container = new ContainerBuilder();
        $config = [
            'strategy'                      => FeatureChecker::STRATEGY_AFFIRMATIVE,
            'allow_if_all_abstain'          => true,
            'allow_if_equal_granted_denied' => false
        ];

        $this->extension->load([$config], $container);

        $this->assertEquals(
            [
                new Reference('oro_featuretoggle.configuration.manager'),
                [],
                $config['strategy'],
                $config['allow_if_all_abstain'],
                $config['allow_if_equal_granted_denied']
            ],
            $container->getDefinition('oro_featuretoggle.checker.feature_checker')->getArguments()
        );
    }
}
