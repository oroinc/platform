<?php

namespace Oro\Bundle\TagBundle\Grid\InlineEditing\InlineEditColumnOptions;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions\GuesserInterface;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provide inline editing options for tags column.
 */
class TagsGuesser implements GuesserInterface
{
    private const SUPPORTED_TYPE = 'tags';

    private EntityRoutingHelper $entityRoutingHelper;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        EntityRoutingHelper $entityRoutingHelper,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->entityRoutingHelper = $entityRoutingHelper;
    }

    public function guessColumnOptions($columnName, $entityName, $column, $isEnabledInline = false)
    {
        $result = [];
        if (\array_key_exists(PropertyInterface::FRONTEND_TYPE_KEY, $column)
            && $column[PropertyInterface::FRONTEND_TYPE_KEY] === self::SUPPORTED_TYPE
        ) {
            $result[Configuration::BASE_CONFIG_KEY] = $this->getInlineOptions($entityName, $isEnabledInline);
        }

        return $result;
    }

    protected function getInlineOptions(string $entityName, bool $isEnabledInline): array
    {
        return [
            'enable' => $isEnabledInline && $this->authorizationChecker->isGranted('oro_tag_assign_unassign'),
            'editor' => [
                'view' => 'orotag/js/app/views/editor/tags-editor-view',
                'view_options' => [
                    'permissions' => [
                        'oro_tag_create' => $this->authorizationChecker->isGranted('oro_tag_create')
                    ]
                ]
            ],
            'save_api_accessor' => [
                'route' => 'oro_api_post_taggable',
                'http_method' => 'POST',
                'default_route_parameters' => [
                    'entity' => $this->entityRoutingHelper->getUrlSafeClassName($entityName)
                ],
                'route_parameters_rename_map' => [
                    'id' => 'entityId'
                ]
            ],
            'autocomplete_api_accessor' => [
                'class' => 'oroui/js/tools/search-api-accessor',
                'search_handler_name' => 'tags',
                'label_field_name' => 'name'
            ]
        ];
    }
}
