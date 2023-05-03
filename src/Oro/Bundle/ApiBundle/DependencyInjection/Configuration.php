<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\PrimaryFieldFilter;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_api');
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'web_api' => ['type' => 'boolean', 'value' => false]
            ]
        );

        $node = $rootNode->children();
        $this->appendOptions($node);
        $this->appendConfigFilesNode($node);
        $this->appendConfigExtensionsNode($node);
        $this->appendApiDocCacheNode($node);
        $this->appendApiDocViewsNode($node);
        $this->appendActionsNode($node);
        $this->appendFiltersNode($node);
        $this->appendFilterOperatorsNode($node);
        $this->appendFormTypesNode($node);
        $this->appendFormTypeExtensionsNode($node);
        $this->appendFormTypeGuessersNode($node);
        $this->appendFormTypeGuessesNode($node);
        $this->appendErrorTitleOverrideNode($node);
        $this->appendCorsNode($node);
        $this->appendFeatureDependedFirewalls($node);
        $this->appendBatchApiNode($node);

        return $treeBuilder;
    }

    private function appendOptions(NodeBuilder $node): void
    {
        $node
            ->scalarNode('rest_api_prefix')
                ->info('The prefix of REST API URLs.')
                ->cannotBeEmpty()
                ->defaultValue('/api/')
            ->end()
            ->scalarNode('rest_api_pattern')
                ->info('The regular expression pattern to which REST API URLs are matched.')
                ->cannotBeEmpty()
                ->defaultValue('^/api/(?!(rest|doc)($|/.*))')
            ->end()
            ->integerNode('config_max_nesting_level')
                ->info(
                    'The maximum number of nesting target entities'
                    . ' that can be specified in "Resources/config/oro/api.yml".'
                )
                ->min(0)
                ->defaultValue(3)
            ->end()
            ->integerNode('default_page_size')
                ->info(
                    'The default page size. It is used when the page size is not specified in a request explicitly.'
                )
                ->min(0)
                ->defaultValue(10)
            ->end()
            ->integerNode('max_entities')
                ->info('The maximum number of primary entities that can be retrieved by a request.')
                ->min(-1)
                ->defaultValue(-1)
                ->validate()
                    ->always(function ($v) {
                        if (0 !== $v) {
                            return $v;
                        }
                        throw new \InvalidArgumentException('Expected a positive number or -1, but got 0.');
                    })
                ->end()
            ->end()
            ->integerNode('max_related_entities')
                ->info('The maximum number of related entities that can be retrieved by a request.')
                ->min(0)
                ->defaultValue(100)
            ->end()
            ->integerNode('max_delete_entities')
                ->info('The maximum number of entities that can be deleted by one request.')
                ->min(0)
                ->defaultValue(100)
            ->end();
    }

    private function appendConfigFilesNode(NodeBuilder $node): void
    {
        $node
            ->arrayNode('config_files')
                ->info('All supported API configuration files.')
                ->validate()
                    ->always(function (array $value) {
                        if (!\array_key_exists('default', $value)) {
                            $value['default'] = ['file_name' => ['api.yml']];
                        }
                        foreach ($value as $k1 => $v1) {
                            $requestType1 = self::getRequestType($v1);
                            foreach ($value as $k2 => $v2) {
                                if ($k1 !== $k2
                                    && self::areRequestTypesEqual($requestType1, self::getRequestType($v2))
                                ) {
                                    throw new \LogicException(sprintf(
                                        'The "request_type" options for "%s" and "%s" are duplicated.',
                                        $k1,
                                        $k2
                                    ));
                                }
                            }
                        }

                        return $value;
                    })
                ->end()
                ->defaultValue(['default' => ['file_name' => ['api.yml']]])
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->beforeNormalization()
                        ->always(function (array $value) {
                            if (!\array_key_exists('file_name', $value)) {
                                $value['file_name'] = 'api.yml';
                            }

                            return $value;
                        })
                    ->end()
                    ->children()
                        ->variableNode('file_name')
                            ->info(
                                'The name of a file that contain API resources configuration.'
                                . ' Can contain several files, in this case all of them are merged.'
                            )
                            ->validate()
                                ->ifTrue(function ($value) {
                                    return !self::isValidConfigFileName($value);
                                })
                                ->thenInvalid('Should be a string or array of strings')
                            ->end()
                            ->validate()
                                ->always(function ($value) {
                                    return (array)$value;
                                })
                            ->end()
                        ->end()
                        ->arrayNode('request_type')
                            ->info('The request type to which this file is applicable.')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function appendApiDocViewsNode(NodeBuilder $node): void
    {
        $node
            ->arrayNode('api_doc_views')
                ->info('All supported API views.')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('label')
                            ->info('The view label.')
                        ->end()
                        ->booleanNode('default')
                            ->info('Whether this view is default one.')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('underlying_view')
                            ->info('The name of the underlying view.')
                        ->end()
                        ->arrayNode('request_type')
                            ->info('The request type supported by this view.')
                            ->prototype('scalar')->end()
                        ->end()
                        ->scalarNode('documentation_path')
                            ->info('The URL to the API documentation for this view.')
                        ->end()
                        ->scalarNode('html_formatter')
                            ->info('The HTML formatter that should be used by this view.')
                            ->defaultValue('oro_api.api_doc.formatter.html_formatter')
                        ->end()
                        ->booleanNode('sandbox')
                            ->info('Whether the sandbox should have a link to this view.')
                            ->defaultTrue()
                        ->end()
                        ->arrayNode('headers')
                            ->info('Headers that should be sent with requests from the sandbox.')
                            ->example([
                                'Accept'       => 'application/vnd.api+json',
                                'Content-Type' => [
                                    ['value' => 'application/vnd.api+json', 'actions' => ['create', 'update']]
                                ],
                                'X-Include'    => [
                                    ['value' => 'totalCount', 'actions' => ['get_list', 'delete_list']],
                                    ['value' => 'deletedCount', 'actions' => ['delete_list']]
                                ]
                            ])
                            ->useAttributeAsKey('name')
                            ->normalizeKeys(false)
                            ->prototype('array')
                                ->beforeNormalization()
                                    ->always(function ($value) {
                                        if (\is_string($value) || $value === null) {
                                            $value = [['value' => $value, 'actions' => []]];
                                        }

                                        return $value;
                                    })
                                ->end()
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('value')
                                            ->info('The header value.')
                                        ->end()
                                        ->arrayNode('actions')
                                            ->info('API actions for which this value should be used.')
                                            ->prototype('scalar')->end()
                                            ->defaultValue([])
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('data_types')
                            ->info('The map between data-type names and their representation in API documentation.')
                            ->example(['guid' => 'string', 'currency' => 'string'])
                            ->useAttributeAsKey('name')
                            ->normalizeKeys(false)
                            ->prototype('scalar')->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->scalarNode('documentation_path')
                ->info(
                    'The URL to the API documentation that should be used for API views'
                    . ' that does not have own documentation.'
                )
                ->defaultNull()
            ->end()
            ->arrayNode('api_doc_data_types')
                ->info(
                    'The map between data-type names and their representation in API documentation. The data-types'
                    . ' declared in this map can be overridden in "data_types" section of a particular API view.'
                )
                ->example(['guid' => 'string', 'currency' => 'string'])
                ->useAttributeAsKey('name')
                ->normalizeKeys(false)
                ->prototype('scalar')->cannotBeEmpty()->end()
            ->end();
        $node->end()
            ->validate()
                ->always(function (array $value) {
                    $documentationPath = $value['documentation_path'];
                    unset($value['documentation_path']);
                    $views = $value['api_doc_views'];
                    foreach (array_keys($views) as $viewName) {
                        if (!empty($views[$viewName]['underlying_view'])) {
                            $underlyingViewName = $views[$viewName]['underlying_view'];
                            self::assertUnderlyingView($views, $underlyingViewName, $viewName);
                            $value['api_doc_views'][$viewName] = self::mergeViews(
                                $views[$viewName],
                                $views[$underlyingViewName]
                            );
                        }
                        if (!\array_key_exists('documentation_path', $views[$viewName])) {
                            $value['api_doc_views'][$viewName]['documentation_path'] = $documentationPath;
                        }
                        if (empty($value['api_doc_views'][$viewName]['data_types'])) {
                            unset($value['api_doc_views'][$viewName]['data_types']);
                        }
                    }

                    return $value;
                })
            ->end();
    }

    private static function assertUnderlyingView(array $views, string $underlyingViewName, string $viewName): void
    {
        if (empty($views[$underlyingViewName])) {
            throw new \LogicException(sprintf(
                'The API view "%s" cannot be used as a underlying view for "%s" API view'
                . ' because it is not defined.',
                $underlyingViewName,
                $viewName
            ));
        }
        if (!empty($views[$underlyingViewName]['underlying_view'])) {
            throw new \LogicException(sprintf(
                'The API view "%s" cannot be used as a underlying view for "%s" API view'
                . ' because only one nesting level of a underlying views is supported.',
                $underlyingViewName,
                $viewName
            ));
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private static function mergeViews(array $view, array $underlyingView): array
    {
        foreach ($underlyingView as $key => $val) {
            if (!\array_key_exists($key, $view)) {
                $view[$key] = $val;
            } elseif ('headers' === $key) {
                foreach ($val as $headerName => $headerValues) {
                    $existingHeaderValues = [];
                    if (!empty($view[$key][$headerName])) {
                        foreach ($view[$key][$headerName] as $headerValue) {
                            $existingHeaderValues[$headerValue['value']] = true;
                        }
                    }
                    foreach ($headerValues as $headerValue) {
                        if (!isset($existingHeaderValues[$headerValue['value']])) {
                            $view[$key][$headerName][] = $headerValue;
                        }
                    }
                }
            } elseif ('data_types' === $key) {
                foreach ($val as $dataType => $docDataType) {
                    if (!isset($view[$key][$dataType])) {
                        $view[$key][$dataType] = $docDataType;
                    }
                }
            }
        }

        return $view;
    }

    private function appendApiDocCacheNode(NodeBuilder $node): void
    {
        $node
            ->arrayNode('api_doc_cache')
                ->info('The configuration of API documentation cache.')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('excluded_features')
                        ->info('The list of features that do not affect API documentation cache.')
                        ->example(['web_api'])
                        ->prototype('scalar')->cannotBeEmpty()->end()
                        ->defaultValue(['web_api'])
                    ->end()
                    ->arrayNode('resettable_services')
                        ->info(
                            'The list of services that should be reset to its initial state'
                            . ' after API documentation cache for a specific view is warmed up.'
                        )
                        ->example(['acme.api.some_provider'])
                        ->prototype('scalar')->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end();
    }

    private function appendConfigExtensionsNode(NodeBuilder $node): void
    {
        $node
            ->arrayNode('config_extensions')
                ->info('The configuration extensions for "Resources/config/oro/api.yml".')
                ->example(['oro_api.config_extension.filters', 'oro_api.config_extension.sorters'])
                ->prototype('scalar')
                ->end()
            ->end();
    }

    private function appendActionsNode(NodeBuilder $node): void
    {
        $node
            ->arrayNode('actions')
                ->info('The definition of API actions.')
                ->example(
                    [
                        'get' => [
                            'processor_service_id' => 'oro_api.get.processor',
                            'processing_groups' => [
                                'load_data' => [
                                    'priority' => -10
                                ],
                                'normalize_data' => [
                                    'priority' => -20
                                ]
                            ]
                        ]
                    ]
                )
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->validate()
                        ->always(function ($value) {
                            if (!empty($value['processing_groups'])) {
                                $priority = 0;
                                foreach ($value['processing_groups'] as &$group) {
                                    if (!isset($group['priority'])) {
                                        $priority--;
                                        $group['priority'] = $priority;
                                    }
                                }
                            }

                            return $value;
                        })
                    ->end()
                    ->children()
                        ->scalarNode('processor_service_id')
                            ->info('The service id of the action processor. Set for public actions only.')
                            ->cannotBeEmpty()
                        ->end()
                        ->arrayNode('processing_groups')
                            ->info('A list of groups by which child processors can be split.')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('priority')
                                        ->info('The priority of the group.')
                                        ->cannotBeEmpty()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function appendFilterOperatorsNode(NodeBuilder $node): void
    {
        $node
            ->arrayNode('filter_operators')
                ->info(
                    'A definition of operators for filters.'
                    . ' The key is the name of an operator.'
                    . ' The value is optional and it is a short name of an operator.'
                )
                ->example([
                    'eq'     => '=',
                    'regexp' => null
                ])
                ->useAttributeAsKey('name')
                ->prototype('scalar')
                ->end()
            ->end();
    }

    private function appendFiltersNode(NodeBuilder $node): void
    {
        $node
            ->arrayNode('filters')
                ->info('The definition of filters.')
                ->example(
                    [
                        'integer' => [
                            'supported_operators' => ['=', '!=', '<', '<=', '>', '>=', '*', '!*']
                        ],
                        'primaryField' => [
                            'class' => PrimaryFieldFilter::class
                        ],
                        'association' => [
                            'factory' => ['@oro_api.filter_factory.association', 'createFilter']
                        ]
                    ]
                )
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->validate()
                        ->always(function ($value) {
                            if (empty($value['factory'])) {
                                unset($value['factory']);
                                if (empty($value['class'])) {
                                    $value['class'] = ComparisonFilter::class;
                                }
                            }

                            return $value;
                        })
                    ->end()
                    ->validate()
                        ->ifTrue(function ($value) {
                            return !empty($value['class']) && !empty($value['factory']);
                        })
                        ->thenInvalid('The "class" and "factory" should not be used together.')
                    ->end()
                    ->children()
                        ->scalarNode('class')
                            ->cannotBeEmpty()
                        ->end()
                        ->arrayNode('factory')
                            ->validate()
                                ->ifTrue(function ($value) {
                                    return \count($value) !== 2 || 0 !== strncmp($value[0], '@', 1);
                                })
                                ->thenInvalid('Expected [\'@serviceId\', \'methodName\']')
                            ->end()
                            ->prototype('scalar')->cannotBeEmpty()->end()
                        ->end()
                        ->arrayNode('supported_operators')
                            ->prototype('scalar')->end()
                            ->defaultValue(['=', '!=', '*', '!*'])
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function appendFormTypesNode(NodeBuilder $node): void
    {
        $node
            ->arrayNode('form_types')
                ->info('The form types that can be reused in API.')
                ->example([
                    FormType::class,
                    'oro_api.form.type.entity'
                ])
                ->prototype('scalar')
                ->end()
            ->end();
    }

    private function appendFormTypeExtensionsNode(NodeBuilder $node): void
    {
        $node
            ->arrayNode('form_type_extensions')
                ->info('The form type extensions that can be reused in API.')
                ->example(['form.type_extension.form.http_foundation'])
                ->prototype('scalar')
                ->end()
            ->end();
    }

    private function appendFormTypeGuessersNode(NodeBuilder $node): void
    {
        $node
            ->arrayNode('form_type_guessers')
                ->info('The form type guessers that can be reused in API.')
                ->example(['form.type_guesser.validator'])
                ->prototype('scalar')
                ->end()
            ->end();
    }

    private function appendFormTypeGuessesNode(NodeBuilder $node): void
    {
        $node
            ->arrayNode('form_type_guesses')
                ->info('The definition of data type to form type guesses.')
                ->example(
                    [
                        'integer' => [
                            'form_type' => IntegerType::class,
                        ],
                        'datetime' => [
                            'form_type' => DateTimeType::class,
                            'options'   => ['model_timezone' => 'UTC', 'view_timezone' => 'UTC']
                        ],
                    ]
                )
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->performNoDeepMerging()
                    ->children()
                        ->scalarNode('form_type')
                            ->cannotBeEmpty()
                        ->end()
                        ->arrayNode('options')
                            ->useAttributeAsKey('name')
                            ->prototype('variable')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function appendErrorTitleOverrideNode(NodeBuilder $node): void
    {
        $node
            ->arrayNode('error_title_overrides')
                ->info('The map between error titles and their substitutions.')
                ->example(['percent range constraint' => 'range constraint'])
                ->useAttributeAsKey('name')
                ->normalizeKeys(false)
                ->prototype('scalar')->cannotBeEmpty()->end()
            ->end();
    }

    private function appendCorsNode(NodeBuilder $node): void
    {
        $node
            ->arrayNode('cors')
                ->info('The configuration of CORS requests.')
                ->addDefaultsIfNotSet()
                ->children()
                    ->integerNode('preflight_max_age')
                        ->info('The amount of seconds the user agent is allowed to cache CORS preflight requests.')
                        ->defaultValue(600)
                        ->min(0)
                    ->end()
                    ->arrayNode('allow_origins')
                        ->info('The list of origins that are allowed to send CORS requests.')
                        ->example(['https://foo.com', 'https://bar.com'])
                        ->prototype('scalar')->cannotBeEmpty()->end()
                    ->end()
                    ->booleanNode('allow_credentials')
                        ->info('Indicates whether CORS request can include user credentials.')
                        ->defaultValue(false)
                    ->end()
                    ->arrayNode('allow_headers')
                        ->info('The list of headers that are allowed to send by CORS requests.')
                        ->example(['X-Foo', 'X-Bar'])
                        ->prototype('scalar')->cannotBeEmpty()->end()
                    ->end()
                    ->arrayNode('expose_headers')
                        ->info('The list of headers that can be exposed by CORS responses.')
                        ->example(['X-Foo', 'X-Bar'])
                        ->prototype('scalar')->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end();
    }

    private function appendFeatureDependedFirewalls(NodeBuilder $node): void
    {
        $node
            ->arrayNode('api_firewalls')
                ->info('The configuration of feature depended API firewalls.')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('feature_name')
                            ->info('The name of the feature.')
                            ->cannotBeEmpty()
                        ->end()
                        ->arrayNode('feature_firewall_listeners')
                            ->info(
                                'The list of security firewall listeners that should be removed'
                                . ' if the feature is disabled.'
                            )
                            ->prototype('scalar')->cannotBeEmpty()->end()
                            ->defaultValue([])
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function appendBatchApiNode(NodeBuilder $node): void
    {
        $batchApiNode = $node
            ->arrayNode('batch_api')
                ->info('The Batch API configuration.')
                ->addDefaultsIfNotSet()
                ->children();
        $batchApiNode
            ->arrayNode('async_operation')
                ->addDefaultsIfNotSet()
                ->children()
                    ->integerNode('lifetime')
                        ->info('The number of days asynchronous operations are stored in the system.')
                        ->min(1)
                        ->defaultValue(30)
                    ->end()
                    ->integerNode('cleanup_process_timeout')
                        ->info(
                            'The maximum number of seconds that the asynchronous operations cleanup process'
                            . ' can spend in one run.'
                        )
                        ->min(60) // 1 minute
                        ->defaultValue(3600) // 1 hour
                    ->end()
                    ->integerNode('operation_timeout')
                        ->info(
                            'The maximum number of seconds after which an operation will be removed'
                            . ' regardless of status.'
                        )
                        ->min(60)
                        ->defaultValue(3600) // 1 hour
                    ->end()
                ->end()
            ->end()
            ->integerNode('chunk_size')
                ->info('The default maximum number of entities that can be saved in a chunk.')
                ->min(1)
                ->defaultValue(100)
            ->end()
            ->integerNode('included_data_chunk_size')
                ->info('The default maximum number of included entities that can be saved in a chunk.')
                ->min(1)
                ->defaultValue(50)
            ->end();

        $chunkSizePerEntityNode = $batchApiNode
            ->arrayNode('chunk_size_per_entity')
                ->info(
                    'The maximum number of entities of a specific type that can be saved in a chunk.'
                    . "\n"
                    . 'The null value can be used to revert already configured chunk size'
                    . ' for a specific entity type and use the default chunk size for it.'
                )
                ->example([User::class => 10]);
        $this->configureChunkSizePerEntity($chunkSizePerEntityNode);

        $includedDataChunkSizePerEntityNode = $batchApiNode
            ->arrayNode('included_data_chunk_size_per_entity')
                ->info(
                    'The maximum number of included entities that can be saved in a chunk'
                    . ' for a specific primary entity type.'
                    . "\n"
                    . 'The null value can be used to revert already configured chunk size'
                    . ' for a specific entity type and use the default chunk size for it.'
                )
                ->example([User::class => 20]);
        $this->configureChunkSizePerEntity($includedDataChunkSizePerEntityNode);
    }

    private function configureChunkSizePerEntity(ArrayNodeDefinition $node): void
    {
        $node
            ->useAttributeAsKey('name')
            ->normalizeKeys(false)
            ->validate()
                ->always(function ($value) {
                    $toRemove = [];
                    foreach ($value as $className => $chunkSize) {
                        if (null === $chunkSize) {
                            $toRemove[] = $className;
                        }
                    }
                    foreach ($toRemove as $className) {
                        unset($value[$className]);
                    }

                    return $value;
                })
            ->end()
            ->prototype('scalar')
                ->validate()
                    ->ifTrue(function ($value) {
                        return null !== $value && !\is_int($value);
                    })
                    ->thenInvalid('Expected int or NULL.')
                ->end()
            ->end();
    }

    private static function isValidConfigFileName(mixed $value): bool
    {
        $isValid = false;
        if (\is_string($value)) {
            $isValid = ('' !== trim($value));
        } elseif (\is_array($value)) {
            $isValid = true;
            foreach ($value as $v) {
                if (!\is_string($v) || '' === trim($v)) {
                    $isValid = false;
                    break;
                }
            }
        }

        return $isValid;
    }

    private static function areRequestTypesEqual(array $requestType1, array $requestType2): bool
    {
        sort($requestType1, SORT_STRING);
        sort($requestType2, SORT_STRING);

        return implode(',', $requestType1) === implode(',', $requestType2);
    }

    private static function getRequestType(array $value): array
    {
        $requestType = $value['request_type'] ?? '';
        if (!\is_array($requestType)) {
            $requestType = [(string)$requestType];
        }

        return $requestType;
    }
}
