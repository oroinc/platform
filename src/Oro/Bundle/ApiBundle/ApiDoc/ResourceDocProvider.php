<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\ApiActions;

class ResourceDocProvider implements ResourceDocProviderInterface
{
    protected $templates = [
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
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getResourceDescription($action, $entityDescription)
    {
        return isset($this->templates[$action])
            ? strtr($this->templates[$action]['description'], ['{name}' => $entityDescription])
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceDocumentation($action, $entityDescription)
    {
        return isset($this->templates[$action]['documentation'])
            ? strtr($this->templates[$action]['documentation'], ['{name}' => $entityDescription])
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubresourceDescription($action, $associationDescription, $isCollection)
    {
        if (!isset($this->templates[$action])) {
            return null;
        }

        $template = $this->templates[$action]['description'];
        if (is_array($template)) {
            $template = $isCollection ? $template['collection'] : $template['single_item'];
        }

        return strtr($template, ['{association}' => $associationDescription]);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubresourceDocumentation($action, $associationDescription, $isCollection)
    {
        if (!isset($this->templates[$action])) {
            return null;
        }

        $template = $this->templates[$action]['documentation'];
        if (is_array($template)) {
            $template = $isCollection ? $template['collection'] : $template['single_item'];
        }

        return strtr($template, ['{association}' => $associationDescription]);
    }
}
