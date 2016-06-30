<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig as EntityConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig as FieldConfig;
use Oro\Bundle\ApiBundle\Provider\SubresourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ResourceDocProvider implements ResourceDocProviderInterface
{
    const ID_DESCRIPTION = 'The identifier of an entity';

    protected $templates = [
        ApiActions::GET                 => [
            'description'          => 'Get {name}',
            'fallback_description' => 'Get {class}'
        ],
        ApiActions::GET_LIST            => [
            'description'          => 'Get {name}',
            'fallback_description' => 'Get a list of {class}',
            'is_collection'        => true
        ],
        ApiActions::DELETE              => [
            'description'          => 'Delete {name}',
            'fallback_description' => 'Delete {class}'
        ],
        ApiActions::DELETE_LIST         => [
            'description'          => 'Delete {name}',
            'fallback_description' => 'Delete a list of {class}',
            'is_collection'        => true
        ],
        ApiActions::CREATE              => [
            'description'          => 'Create {name}',
            'fallback_description' => 'Create {class}'
        ],
        ApiActions::UPDATE              => [
            'description'          => 'Update {name}',
            'fallback_description' => 'Update {class}'
        ],
        ApiActions::GET_SUBRESOURCE     => [
            'description'   => 'Get {association}',
            'documentation' => 'Get related entities'
        ],
        ApiActions::GET_RELATIONSHIP    => [
            'description'   => 'Get "{association}" relationship',
            'documentation' => 'Get the relationship data'
        ],
        ApiActions::DELETE_RELATIONSHIP => [
            'description'   => 'Delete members from "{association}" relationship',
            'documentation' => 'Delete the specified members from the relationship'
        ],
        ApiActions::ADD_RELATIONSHIP    => [
            'description'   => 'Add members to "{association}" relationship',
            'documentation' => 'Delete the specified members to the relationship'
        ],
        ApiActions::UPDATE_RELATIONSHIP => [
            'description'   => [
                'single_item' => 'Update "{association}" relationship',
                'collection'  => 'Replace "{association}" relationship',
            ],
            'documentation' => [
                'single_item' => 'Update the relationship',
                'collection'  => 'Completely replace every member of the relationship',
            ]
        ],
    ];

    /** @var SubresourcesProvider */
    protected $subresourcesProvider;

    /**
     * @param SubresourcesProvider $subresourcesProvider
     */
    public function __construct(SubresourcesProvider $subresourcesProvider)
    {
        $this->subresourcesProvider = $subresourcesProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierDescription(RequestType $requestType)
    {
        return self::ID_DESCRIPTION;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceDescription(
        $action,
        $version,
        RequestType $requestType,
        array $config,
        $entityClass = null
    ) {
        $templates = $this->templates[$action];

        $description = null;
        $descriptionConfigKey = isset($templates['is_collection']) && $templates['is_collection']
            ? EntityConfig::PLURAL_LABEL
            : EntityConfig::LABEL;
        if (!empty($config[$descriptionConfigKey])) {
            $description = $config[$descriptionConfigKey];
        }
        if ($description) {
            $description = strtr($templates['description'], ['{name}' => $description]);
        } elseif ($entityClass) {
            $description = strtr($templates['fallback_description'], ['{class}' => $entityClass]);
        }

        return $description;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceDocumentation(
        $action,
        $version,
        RequestType $requestType,
        array $config,
        $entityClass = null
    ) {
        $documentation = null;
        if (!empty($config[EntityConfig::DESCRIPTION])) {
            $documentation = $config[EntityConfig::DESCRIPTION];
        }

        return $documentation;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubresourceDescription(
        $action,
        $version,
        RequestType $requestType,
        array $config,
        $entityClass,
        $associationName
    ) {
        $template = $this->templates[$action]['description'];
        if (is_array($template)) {
            $subresource = $this->getSubresource($version, $requestType, $entityClass, $associationName);
            $template = $subresource && $subresource->isCollection()
                ? $template['collection']
                : $template['single_item'];
        }
        $association = !empty($config[EntityConfig::FIELDS][$associationName][FieldConfig::LABEL])
            ? $config[EntityConfig::FIELDS][$associationName][FieldConfig::LABEL]
            : $this->humanizeAssociationName($associationName);

        return strtr($template, ['{association}' => $association]);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubresourceDocumentation(
        $action,
        $version,
        RequestType $requestType,
        array $config,
        $entityClass,
        $associationName
    ) {
        $template = $this->templates[$action]['documentation'];
        if (is_array($template)) {
            $subresource = $this->getSubresource($version, $requestType, $entityClass, $associationName);
            $template = $subresource && $subresource->isCollection()
                ? $template['collection']
                : $template['single_item'];
        }

        return $template;
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     * @param string      $entityClass
     * @param string      $associationName
     *
     * @return ApiSubresource|null
     */
    protected function getSubresource(
        $version,
        RequestType $requestType,
        $entityClass,
        $associationName
    ) {
        $entitySubresources = $this->subresourcesProvider->getSubresources($entityClass, $version, $requestType);

        return $entitySubresources
            ? $entitySubresources->getSubresource($associationName)
            : null;
    }

    /**
     * @param string $associationName
     *
     * @return string
     */
    protected function humanizeAssociationName($associationName)
    {
        return strtolower(
            str_replace('_', ' ', preg_replace('/(?<=[^A-Z])([A-Z])/', ' $1', $associationName))
        );
    }
}
