<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oro_api');

        $node = $rootNode->children();
        $this->appendOptions($node);
        $this->appendConfigFilesNode($node);
        $this->appendApiDocViewsNode($node);
        $this->appendConfigExtensionsNode($node);
        $this->appendActionsNode($node);
        $this->appendFilterOperatorsNode($node);
        $this->appendFiltersNode($node);
        $this->appendFormTypesNode($node);
        $this->appendFormTypeExtensionsNode($node);
        $this->appendFormTypeGuessersNode($node);
        $this->appendFormTypeGuessesNode($node);
        $this->appendCorsNode($node);

        return $treeBuilder;
    }

    /**
     * @param NodeBuilder $node
     */
    private function appendOptions(NodeBuilder $node)
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
                    . ' that can be specified in "Resources/config/oro/api.yml"'
                )
                ->min(0)
                ->defaultValue(3)
            ->end();
    }

    /**
     * @param NodeBuilder $node
     */
    private function appendConfigFilesNode(NodeBuilder $node)
    {
        $node
            ->arrayNode('config_files')
                ->info('All supported Data API configuration files')
                ->validate()
                    ->always(function (array $value) {
                        if (!array_key_exists('default', $value)) {
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
                            if (!array_key_exists('file_name', $value)) {
                                $value['file_name'] = 'api.yml';
                            }

                            return $value;
                        })
                    ->end()
                    ->children()
                        ->variableNode('file_name')
                            ->info(
                                'The name of a file that contain Data API resources configuration.'
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
                            ->info('The request type for which this file is applicable.')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param NodeBuilder $node
     */
    private function appendApiDocViewsNode(NodeBuilder $node)
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
                                'Content-Type' => 'application/vnd.api+json',
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
                                        if (is_string($value)) {
                                            $value = [['value' => $value, 'actions' => []]];
                                        }

                                        return $value;
                                    })
                                ->end()
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('value')
                                            ->info('The header value.')
                                            ->cannotBeEmpty()
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
                    ->end()
                ->end()
            ->end()
            ->scalarNode('documentation_path')
                ->info(
                    'The URL to the API documentation that should be used for API views'
                    . ' that does not have own documentation.'
                )
                ->defaultNull()
            ->end();
        $node->end()
            ->validate()
                ->always(function (array $value) {
                    $documentationPath = $value['documentation_path'];
                    unset($value['documentation_path']);
                    foreach ($value['api_doc_views'] as $key => $view) {
                        if (!\array_key_exists('documentation_path', $view)) {
                            $value['api_doc_views'][$key]['documentation_path'] = $documentationPath;
                        }
                    }

                    return $value;
                })
            ->end();
    }

    /**
     * @param NodeBuilder $node
     */
    private function appendConfigExtensionsNode(NodeBuilder $node)
    {
        $node
            ->arrayNode('config_extensions')
                ->info('The configuration extensions for "Resources/config/oro/api.yml".')
                ->example(['oro_api.config_extension.filters', 'oro_api.config_extension.sorters'])
                ->prototype('scalar')
                ->end()
            ->end();
    }

    /**
     * @param NodeBuilder $node
     */
    private function appendActionsNode(NodeBuilder $node)
    {
        $node
            ->arrayNode('actions')
                ->info('A definition of Data API actions')
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
                            ->info('A list of groups by which child processors can be split')
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

    /**
     * @param NodeBuilder $node
     */
    private function appendFilterOperatorsNode(NodeBuilder $node)
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

    /**
     * @param NodeBuilder $node
     */
    private function appendFiltersNode(NodeBuilder $node)
    {
        $node
            ->arrayNode('filters')
                ->info('A definition of filters')
                ->example(
                    [
                        'integer' => [
                            'supported_operators' => ['=', '!=', '<', '<=', '>', '>=', '*', '!*']
                        ],
                        'primaryField' => [
                            'class' => 'Oro\Bundle\ApiBundle\Filter\PrimaryFieldFilter'
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
                                    $value['class'] = 'Oro\Bundle\ApiBundle\Filter\ComparisonFilter';
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
                                    return count($value) !== 2 || 0 !== strpos($value[0], '@');
                                })
                                ->thenInvalid('Expected [\'@serviceId\', \'methodName\']')
                            ->end()
                            ->prototype('scalar')->cannotBeEmpty()->end()
                        ->end()
                        ->arrayNode('supported_operators')
                            ->prototype('scalar')->end()
                            ->cannotBeEmpty()
                            ->defaultValue(['=', '!=', '*', '!*'])
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param NodeBuilder $node
     */
    private function appendFormTypesNode(NodeBuilder $node)
    {
        $node
            ->arrayNode('form_types')
                ->info('The form types that can be reused in Data API')
                ->example([
                    'Symfony\Component\Form\Extension\Core\Type\FormType',
                    'oro_api.form.type.entity'
                ])
                ->prototype('scalar')
                ->end()
            ->end();
    }

    /**
     * @param NodeBuilder $node
     */
    private function appendFormTypeExtensionsNode(NodeBuilder $node)
    {
        $node
            ->arrayNode('form_type_extensions')
                ->info('The form type extensions that can be reused in Data API')
                ->example(['form.type_extension.form.http_foundation'])
                ->prototype('scalar')
                ->end()
            ->end();
    }

    /**
     * @param NodeBuilder $node
     */
    private function appendFormTypeGuessersNode(NodeBuilder $node)
    {
        $node
            ->arrayNode('form_type_guessers')
                ->info('The form type guessers that can be reused in Data API')
                ->example(['form.type_guesser.validator'])
                ->prototype('scalar')
                ->end()
            ->end();
    }

    /**
     * @param NodeBuilder $node
     */
    private function appendFormTypeGuessesNode(NodeBuilder $node)
    {
        $node
            ->arrayNode('form_type_guesses')
                ->info('A definition of data type to form type guesses')
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

    /**
     * @param NodeBuilder $node
     */
    private function appendCorsNode(NodeBuilder $node)
    {
        $node
            ->arrayNode('cors')
                ->info('The configuration of CORS requests')
                ->addDefaultsIfNotSet()
                ->children()
                    ->integerNode('preflight_max_age')
                        ->info('The amount of seconds the user agent is allowed to cache CORS preflight requests')
                        ->defaultValue(600)
                        ->min(0)
                    ->end()
                    ->arrayNode('allow_origins')
                        ->info('The list of origins that are allowed to send CORS requests')
                        ->example(['https://foo.com', 'https://bar.com'])
                        ->prototype('scalar')->cannotBeEmpty()->end()
                    ->end()
                    ->booleanNode('allow_credentials')
                        ->info('Indicates whether CORS request can include user credentials')
                        ->defaultValue(false)
                    ->end()
                    ->arrayNode('allow_headers')
                        ->info('The list of headers that are allowed to send by CORS requests')
                        ->example(['X-Foo', 'X-Bar'])
                        ->prototype('scalar')->cannotBeEmpty()->end()
                    ->end()
                    ->arrayNode('expose_headers')
                        ->info('The list of headers that can be exposed by CORS responses')
                        ->example(['X-Foo', 'X-Bar'])
                        ->prototype('scalar')->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private static function isValidConfigFileName($value)
    {
        $isValid = false;
        if (is_string($value)) {
            $isValid = ('' !== trim($value));
        } elseif (is_array($value)) {
            $isValid = true;
            foreach ($value as $v) {
                if (!is_string($v) || '' === trim($v)) {
                    $isValid = false;
                    break;
                }
            }
        }

        return $isValid;
    }

    /**
     * @param array $requestType1
     * @param array $requestType2
     *
     * @return bool
     */
    private static function areRequestTypesEqual(array $requestType1, array $requestType2)
    {
        sort($requestType1, SORT_STRING);
        sort($requestType2, SORT_STRING);

        return implode(',', $requestType1) === implode(',', $requestType2);
    }

    /**
     * @param array $value
     *
     * @return string[]
     */
    private static function getRequestType(array $value): array
    {
        $requestType = null;
        if (array_key_exists('request_type', $value)) {
            $requestType = $value['request_type'];
        }
        if (null === $requestType) {
            $requestType = '';
        }
        if (!is_array($requestType)) {
            $requestType = [(string)$requestType];
        }

        return $requestType;
    }
}
