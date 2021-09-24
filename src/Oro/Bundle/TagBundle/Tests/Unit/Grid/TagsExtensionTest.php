<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Grid\TagsExtension;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TagsExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TagManager|MockObject
     */
    private $tagManager;

    /**
     * @var EntityClassResolver|MockObject
     */
    private $entityClassResolver;

    /**
     * @var TaggableHelper|MockObject
     */
    private $taggableHelper;

    /**
     * @var EntityRoutingHelper|MockObject
     */
    private $entityRoutingHelper;

    /**
     * @var AuthorizationCheckerInterface|MockObject
     */
    private $authorizationChecker;

    /**
     * @var TokenStorageInterface|MockObject
     */
    private $tokenStorage;

    /**
     * @var TagsExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->tagManager = $this->createMock(TagManager::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->taggableHelper = $this->createMock(TaggableHelper::class);
        $this->entityRoutingHelper = $this->createMock(EntityRoutingHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->extension = new TagsExtension(
            $this->tagManager,
            $this->entityClassResolver,
            $this->taggableHelper,
            $this->entityRoutingHelper,
            $this->authorizationChecker,
            $this->tokenStorage
        );
        $this->extension->setParameters(new ParameterBag());
    }

    public function testGetPriority()
    {
        $this->assertEquals(10, $this->extension->getPriority());
    }

    /**
     * @dataProvider parametersDataProvider
     */
    public function testIsApplicable(
        array $parameters,
        bool $isTaggable,
        bool $isGranted,
        ?TokenInterface $token,
        bool $expected
    ) {
        $config = DatagridConfiguration::create($parameters);
        $config->setName('test_grid');

        $this->taggableHelper->expects($this->any())
            ->method('isTaggable')
            ->with('Test')
            ->willReturn($isTaggable);
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->with('oro_tag_view')
            ->willReturn($isGranted);

        $this->assertEquals($expected, $this->extension->isApplicable($config));
    }

    public function parametersDataProvider()
    {
        yield [
            [
                'extended_entity_name' => 'Test',
                'source' => ['type' => 'orm'],
                'properties' => ['id' => 'id']
            ],
            true,
            true,
            $this->createMock(TokenInterface::class),
            true
        ];

        yield [
            [
                'extended_entity_name' => 'Test',
                'source' => ['type' => 'orm'],
                'properties' => ['id' => 'id']
            ],
            false,
            true,
            $this->createMock(TokenInterface::class),
            false
        ];

        yield [
            [
                'extended_entity_name' => 'Test',
                'source' => ['type' => 'orm'],
                'properties' => ['id' => 'id']
            ],
            true,
            false,
            $this->createMock(TokenInterface::class),
            false
        ];

        yield [
            [
                'extended_entity_name' => 'Test',
                'source' => ['type' => 'orm'],
                'properties' => ['id' => 'id']
            ],
            true,
            true,
            null,
            false
        ];

        yield [
            [
                'extended_entity_name' => null,
                'source' => ['type' => 'orm'],
                'properties' => ['id' => 'id']
            ],
            true,
            true,
            $this->createMock(TokenInterface::class),
            false
        ];

        yield [
            [
                'extended_entity_name' => 'Test',
                'source' => ['type' => 'array'],
                'properties' => ['id' => 'id']
            ],
            true,
            true,
            $this->createMock(TokenInterface::class),
            false
        ];

        yield [
            [
                'extended_entity_name' => 'Test',
                'source' => ['type' => 'orm'],
                'properties' => []
            ],
            true,
            true,
            $this->createMock(TokenInterface::class),
            false
        ];
    }

    public function testProcessConfigs()
    {
        $config = DatagridConfiguration::create([
            'extended_entity_name' => 'Test',
            'columns' => [
                'column1' => [
                    'label' => 'Column 1'
                ]
            ],
            'filters' => [
                'columns' => [
                    'id' => []
                ]
            ]
        ]);

        $this->entityRoutingHelper->expects($this->once())
            ->method('getUrlSafeClassName')
            ->with('Test')
            ->willReturn('TestSafe');

        $this->extension->processConfigs($config);

        $actualParameters = $config->toArray();
        $this->assertArrayHasKey('tags', $actualParameters['columns']);
        $this->assertArrayHasKey('tagname', $actualParameters['filters']['columns']);
        $this->assertTrue($actualParameters['inline_editing']['enable']);
        $this->assertEquals('enable_selected', $actualParameters['inline_editing']['behaviour']);
    }
}
