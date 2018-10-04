<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\ApiActions;

/**
 * Provides default descriptions in English for API resources.
 */
class ResourceDocProvider
{
    private const TEMPLATES = [
        ApiActions::OPTIONS             => [
            'description'   => 'Get options',
            'documentation' => 'Get communication options for a resource'
        ],
        ApiActions::GET                 => [
            'description'   => 'Get {name}',
            'documentation' => 'Get an entity'
        ],
        ApiActions::GET_LIST            => [
            'description'   => 'Get {name}',
            'documentation' => 'Get a list of entities'
        ],
        ApiActions::DELETE              => [
            'description'   => 'Delete {name}',
            'documentation' => 'Delete an entity'
        ],
        ApiActions::DELETE_LIST         => [
            'description'   => 'Delete {name}',
            'documentation' => 'Delete a list of entities'
        ],
        ApiActions::CREATE              => [
            'description'   => 'Create {name}',
            'documentation' => 'Create an entity'
        ],
        ApiActions::UPDATE              => [
            'description'   => 'Update {name}',
            'documentation' => 'Update an entity'
        ],
        ApiActions::GET_SUBRESOURCE     => [
            'description'   => 'Get {association}',
            'documentation' => [
                'single_item' => 'Get a related entity',
                'collection'  => 'Get a list of related entities'
            ]
        ],
        ApiActions::DELETE_SUBRESOURCE  => [
            'description'   => 'Delete {association}',
            'documentation' => [
                'single_item' => 'Delete the specified related entity',
                'collection'  => 'Delete the specified related entities'
            ]
        ],
        ApiActions::ADD_SUBRESOURCE     => [
            'description'   => 'Add {association}',
            'documentation' => [
                'single_item' => 'Add the specified related entity',
                'collection'  => 'Add the specified related entities'
            ]
        ],
        ApiActions::UPDATE_SUBRESOURCE  => [
            'description'   => 'Update {association}',
            'documentation' => [
                'single_item' => 'Update the specified related entity',
                'collection'  => 'Update the specified related entities'
            ]
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
            'documentation' => 'Add the specified members to the relationship'
        ],
        ApiActions::UPDATE_RELATIONSHIP => [
            'description'   => [
                'single_item' => 'Update "{association}" relationship',
                'collection'  => 'Replace "{association}" relationship'
            ],
            'documentation' => [
                'single_item' => 'Update the relationship',
                'collection'  => 'Completely replace every member of the relationship'
            ]
        ]
    ];

    /**
     * Gets a short, human-readable description of API resource.
     *
     * @param string $action
     * @param string $entityDescription
     *
     * @return string|null
     */
    public function getResourceDescription(string $action, string $entityDescription): ?string
    {
        return isset(self::TEMPLATES[$action])
            ? \strtr(self::TEMPLATES[$action]['description'], ['{name}' => $entityDescription])
            : null;
    }

    /**
     * Gets a detailed documentation of API resource.
     *
     * @param string $action
     * @param string $entityDescription
     *
     * @return string|null
     */
    public function getResourceDocumentation(string $action, string $entityDescription): ?string
    {
        return isset(self::TEMPLATES[$action]['documentation'])
            ? \strtr(self::TEMPLATES[$action]['documentation'], ['{name}' => $entityDescription])
            : null;
    }

    /**
     * Gets a short, human-readable description of API sub-resource.
     *
     * @param string $action
     * @param string $associationDescription
     * @param bool   $isCollection
     *
     * @return string|null
     */
    public function getSubresourceDescription(
        string $action,
        string $associationDescription,
        bool $isCollection
    ): ?string {
        if (!isset(self::TEMPLATES[$action])) {
            return null;
        }

        $template = self::TEMPLATES[$action]['description'];
        if (\is_array($template)) {
            $template = $isCollection ? $template['collection'] : $template['single_item'];
        }

        return \strtr($template, ['{association}' => $associationDescription]);
    }

    /**
     * Gets a detailed documentation of API sub-resource.
     *
     * @param string $action
     * @param string $associationDescription
     * @param bool   $isCollection
     *
     * @return string|null
     */
    public function getSubresourceDocumentation(
        string $action,
        string $associationDescription,
        bool $isCollection
    ): ?string {
        if (!isset(self::TEMPLATES[$action])) {
            return null;
        }

        $template = self::TEMPLATES[$action]['documentation'];
        if (\is_array($template)) {
            $template = $isCollection ? $template['collection'] : $template['single_item'];
        }

        return \strtr($template, ['{association}' => $associationDescription]);
    }
}
