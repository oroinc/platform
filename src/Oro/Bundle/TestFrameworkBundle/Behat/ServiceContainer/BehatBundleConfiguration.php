<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer;

use Behat\Symfony2Extension\ServiceContainer\Symfony2Extension;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Configuration defenition of `/Tests/Behat/behat.yml` for bundles.
 */
class BehatBundleConfiguration implements ConfigurationInterface
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
        $this->kernel = $container->get(Symfony2Extension::KERNEL_ID);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oro_behat_extension');

        $rootNode
            ->children()
                ->arrayNode('suites')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->beforeNormalization()
                            ->ifTrue(function ($suite) {
                                return is_array($suite) && count($suite);
                            })
                            ->then(function ($suite) {
                                $suite['settings'] = isset($suite['settings'])
                                    ? $suite['settings']
                                    : [];

                                foreach ($suite as $key => $val) {
                                    $suiteKeys = ['enabled', 'type', 'settings'];
                                    if ('paths' === $key) {
                                        $val = array_map(function ($v) {
                                            if ('@' === substr($v, 0, 1)) {
                                                $bundleName = explode('/', substr($v, 1))[0];
                                                $bundlePath = $this->kernel->getBundle('!' . $bundleName)->getPath();

                                                return str_replace('@'.$bundleName, $bundlePath, $v);
                                            }

                                            return $v;
                                        }, $val);
                                    }
                                    if (!in_array($key, $suiteKeys)) {
                                        $suite['settings'][$key] = $val;
                                        unset($suite[$key]);
                                    }
                                }

                                return $suite;
                            })
                        ->end()
                        ->normalizeKeys(false)
                        ->addDefaultsIfNotSet()
                        ->treatTrueLike(['enabled' => true])
                        ->treatNullLike(['enabled' => true])
                        ->treatFalseLike(['enabled' => false])
                        ->children()
                            ->booleanNode('enabled')
                                ->info('Enables/disables suite')
                                ->defaultTrue()
                            ->end()
                            ->scalarNode('type')
                                ->info('Specifies suite type')
                                ->defaultValue(null)
                            ->end()
                            ->arrayNode('settings')
                                ->info('Specifies suite extra settings')
                                ->defaultValue([])
                                ->useAttributeAsKey('name')
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('elements')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('selector')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function ($value) {
                                        return ['type' => 'css', 'locator' => $value];
                                    })
                                ->end()
                                ->children()
                                    ->scalarNode('type')->isRequired()
                                        ->validate()
                                            ->ifNotInArray(['css', 'xpath'])
                                            ->thenInvalid('Invalid selector type %s')
                                        ->end()
                                    ->end()
                                    ->scalarNode('locator')->isRequired()->end()
                                ->end()
                            ->end()
                            ->scalarNode('class')
                                ->defaultValue('Oro\Bundle\TestFrameworkBundle\Behat\Element\Element')
                            ->end()
                            ->variableNode('options')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('pages')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('class')->isRequired()->end()
                            ->scalarNode('route')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('optional_listeners')
                    ->children()
                        ->arrayNode('required_for_fixtures')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
