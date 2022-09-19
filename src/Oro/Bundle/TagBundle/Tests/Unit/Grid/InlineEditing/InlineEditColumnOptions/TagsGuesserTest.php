<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Grid\InlineEditing\InlineEditColumnOptions;

use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\TagBundle\Grid\InlineEditing\InlineEditColumnOptions\TagsGuesser;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TagsGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityRoutingHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRoutingHelper;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TagsGuesser */
    private $guesser;

    protected function setUp(): void
    {
        $this->entityRoutingHelper = $this->createMock(EntityRoutingHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->guesser = new TagsGuesser(
            $this->entityRoutingHelper,
            $this->authorizationChecker
        );
    }

    public function testGuessColumnOptions()
    {
        $this->authorizationChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->withConsecutive(['oro_tag_assign_unassign'], ['oro_tag_create'])
            ->willReturn(true);
        $this->entityRoutingHelper->expects($this->once())
            ->method('getUrlSafeClassName')
            ->willReturn('TestSafe');

        $opts = $this->guesser->guessColumnOptions(
            'tags',
            'Test',
            ['label' => 'Tags', 'frontend_type' => 'tags'],
            true
        );

        $this->assertEquals(
            [
                'inline_editing' => [
                    'enable' => true,
                    'editor' => [
                        'view' => 'orotag/js/app/views/editor/tags-editor-view',
                        'view_options' => [
                            'permissions' => ['oro_tag_create' => true]
                        ]
                    ],
                    'save_api_accessor' => [
                        'route' => 'oro_api_post_taggable',
                        'http_method' => 'POST',
                        'default_route_parameters' => [
                            'entity' => 'TestSafe'
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
                ]
            ],
            $opts
        );
    }
}
