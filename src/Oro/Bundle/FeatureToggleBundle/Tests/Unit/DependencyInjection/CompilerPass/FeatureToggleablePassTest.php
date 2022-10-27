<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass\FeatureToggleablePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FeatureToggleablePassTest extends \PHPUnit\Framework\TestCase
{
    private const CHECKER_SERVICE_ID = 'oro_featuretoggle.checker.feature_checker';

    /** @var FeatureToggleablePass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new FeatureToggleablePass();
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->register(self::CHECKER_SERVICE_ID);
        $container->register('service1')
            ->addTag('oro_featuretogle.feature', ['feature' => 'feature1']);
        $container->register('service2')
            ->addTag('oro_featuretogle.feature');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addFeature', ['feature1']],
                ['setFeatureChecker', [new Reference(self::CHECKER_SERVICE_ID)]]
            ],
            $container->getDefinition('service1')->getMethodCalls()
        );
        self::assertEquals(
            [
                ['setFeatureChecker', [new Reference(self::CHECKER_SERVICE_ID)]]
            ],
            $container->getDefinition('service2')->getMethodCalls()
        );
    }
}
