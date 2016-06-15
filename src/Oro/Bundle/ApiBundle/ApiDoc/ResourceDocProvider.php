<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig as EntityConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig as FieldConfig;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;

class ResourceDocProvider implements ResourceDocProviderInterface
{
    const ID_DESCRIPTION = 'The identifier of an entity';

    protected $templates = [
        'get'                 => [
            'description'          => 'Get {name}',
            'fallback_description' => 'Get {class}',
            'get_name_method'      => 'getEntityClassName'
        ],
        'get_list'            => [
            'description'          => 'Get {name}',
            'fallback_description' => 'Get a list of {class}',
            'get_name_method'      => 'getEntityClassPluralName',
            'is_collection'        => true
        ],
        'delete'              => [
            'description'          => 'Delete {name}',
            'fallback_description' => 'Delete {class}',
            'get_name_method'      => 'getEntityClassName'
        ],
        'delete_list'         => [
            'description'          => 'Delete {name}',
            'fallback_description' => 'Delete a list of {class}',
            'get_name_method'      => 'getEntityClassName',
            'is_collection'        => true
        ],
        'create'              => [
            'description'          => 'Create {name}',
            'fallback_description' => 'Create {class}',
            'get_name_method'      => 'getEntityClassName'
        ],
        'update'              => [
            'description'          => 'Update {name}',
            'fallback_description' => 'Update {class}',
            'get_name_method'      => 'getEntityClassName'
        ],
        'get_subresource'     => [
            'description'   => 'Get {association}',
            'documentation' => 'Get related entities'
        ],
        'get_relationship'    => [
            'description'   => 'Get "{association}" relationship',
            'documentation' => 'Get the relationship data'
        ],
        'delete_relationship' => [
            'description'   => 'Delete members from "{association}" relationship',
            'documentation' => 'Delete the specified members from the relationship'
        ],
        'add_relationship'    => [
            'description'   => 'Add members to "{association}" relationship',
            'documentation' => 'Delete the specified members to the relationship'
        ],
        'update_relationship' => [
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

    /** @var EntityClassNameProviderInterface */
    protected $entityClassNameProvider;

    /** @var SubresourcesProvider */
    protected $subresourcesProvider;

    /**
     * @param EntityClassNameProviderInterface $entityClassNameProvider
     * @param SubresourcesProvider             $subresourcesProvider
     */
    public function __construct(
        EntityClassNameProviderInterface $entityClassNameProvider,
        SubresourcesProvider $subresourcesProvider
    ) {
        $this->entityClassNameProvider = $entityClassNameProvider;
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
            $entityName = $this->entityClassNameProvider->{$templates['get_name_method']}($entityClass);
            $description = $entityName
                ? strtr($templates['description'], ['{name}' => $entityName])
                : strtr($templates['fallback_description'], ['{class}' => $entityClass]);
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
        if (!$documentation && $entityClass) {
            $templates = $this->templates[$action];
            $entityName = $this->entityClassNameProvider->{$templates['get_name_method']}($entityClass);
            if ($entityName) {
                $documentation = $entityName;
            }
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
