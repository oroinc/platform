<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Cookie;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_security');
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'symfony_profiler_collection_of_voter_decisions' => [
                    'value' => false
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
                ->info(
                    "List of routes that must not be used as a redirect path after log in. ".
                    "See \Oro\Bundle\SecurityBundle\Http\Firewall\ExceptionListener."
                )
            ->end()
        ->end();

        return $treeBuilder;
    }
}
