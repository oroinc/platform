<?php

namespace Oro\Bundle\EmbeddedFormBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const SESSION_ID_FIELD_NAME       = 'session_id_field_name';
    const CSRF_TOKEN_LIFETIME         = 'csrf_token_lifetime';
    const CSRF_TOKEN_CACHE_SERVICE_ID = 'csrf_token_cache_service_id';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oro_embedded_form');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode(self::SESSION_ID_FIELD_NAME)
                    ->info(
                        'The name of the hidden field that should be used to pass the session id'
                        . ' to third party site. This allows to use the embedded form even if'
                        . ' a web browser blocks third-party cookies.'
                    )
                    ->cannotBeEmpty()
                    ->defaultValue('_embedded_form_sid')
                ->end()
                ->integerNode(self::CSRF_TOKEN_LIFETIME)
                    ->info('The number of seconds the CSRF token should live for.')
                    ->min(1)
                    ->defaultValue(3600)
                ->end()
                ->scalarNode(self::CSRF_TOKEN_CACHE_SERVICE_ID)
                    ->info('The service id that is used to cache CSRF tokens.')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
