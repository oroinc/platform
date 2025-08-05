<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Cookie;

class Configuration implements ConfigurationInterface
{
    protected const string ROOT_NODE = 'oro_security';
    public const string SYSTEM_CHECK_CRYPTER_KEY = 'system_check_symmetric_crypter_key';
    public const string SYMFONY_PROFILER_COLLECTION = 'symfony_profiler_collection_of_voter_decisions';

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                self::SYMFONY_PROFILER_COLLECTION => [
                    'value' => false
                ],
                self::SYSTEM_CHECK_CRYPTER_KEY => [
                    'value' => '',
                    'type' => 'string',
                ],
            ]
        );

        $rootNode->children()
            ->arrayNode('csrf_cookie')
                // More info about CSRF cookie configuration can be found at
                // https://doc.oroinc.com/backend/setup/post-install/cookies-configuration/#csrf-cookie
                ->addDefaultsIfNotSet()
                ->children()
                    ->enumNode('cookie_secure')->values([true, false, 'auto'])->defaultValue('auto')->end()
                    ->enumNode('cookie_samesite')
                        ->values([null, Cookie::SAMESITE_LAX, Cookie::SAMESITE_STRICT, Cookie::SAMESITE_NONE])
                        ->defaultValue(Cookie::SAMESITE_LAX)
                        ->end()
                ->end()
            ->end()
            ->arrayNode('login_target_path_excludes')
                ->normalizeKeys(false)
                ->defaultValue([])
                ->prototype('variable')
                ->end()
                ->info(
                    "List of routes that must not be used as a redirect path after log in. ".
                    "See \Oro\Bundle\SecurityBundle\Http\Firewall\ExceptionListener."
                )
            ->end()
            ->arrayNode('access_control')
                ->prototype('array')
                    ->fixXmlConfig('ip')
                    ->fixXmlConfig('method')
                    ->children()
                        ->scalarNode('requires_channel')->defaultNull()->end()
                        ->scalarNode('path')
                            ->defaultNull()
                            ->info('use the urldecoded format')
                            ->example('^/path to resource/')
                        ->end()
                        ->scalarNode('host')->defaultNull()->end()
                        ->integerNode('priority')->defaultValue(0)->end()
                        ->arrayNode('options')
                            ->info('List of additional access control options')
                            ->normalizeKeys(false)
                            ->useAttributeAsKey('name')
                            ->variablePrototype()
                            ->end()
                        ->end()
                        ->integerNode('port')->defaultNull()->end()
                        ->arrayNode('ips')
                            ->beforeNormalization()->ifString()
                            ->then(
                                function ($v) {
                                    return [$v];
                                }
                            )
                            ->end()
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('methods')
                            ->beforeNormalization()
                            ->ifString()
                            ->then(
                                function ($v) {
                                    return preg_split('/\s*,\s*/', $v);
                                }
                            )
                            ->end()
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('allow_if')->defaultNull()->end()
                    ->end()
                    ->fixXmlConfig('role')
                    ->children()
                        ->arrayNode('roles')
                            ->beforeNormalization()
                            ->ifString()
                            ->then(
                                function ($v) {
                                    return preg_split('/\s*,\s*/', $v);
                                }
                            )
                            ->end()
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('permissions_policy')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('enable')->defaultFalse()->end()
                    ->arrayNode('directives')
                        ->useAttributeAsKey('name')
                        ->prototype('variable')
                            ->beforeNormalization()->castToArray()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }

    public static function getConfigKey(string $key): string
    {
        return TreeUtils::getConfigKey(self::ROOT_NODE, $key);
    }
}
