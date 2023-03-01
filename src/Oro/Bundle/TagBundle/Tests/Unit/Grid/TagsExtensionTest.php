<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditingConfigurator;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Grid\TagsExtension;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TagsExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var TagManager|\PHPUnit\Framework\MockObject\MockObject */
    private $tagManager;

    /** @var EntityClassResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassResolver;

    /** @var TaggableHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $taggableHelper;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var InlineEditingConfigurator|\PHPUnit\Framework\MockObject\MockObject */
    private $inlineEditingConfigurator;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject  */
    private $featureChecker;

    /** @var TagsExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->tagManager = $this->createMock(TagManager::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->taggableHelper = $this->createMock(TaggableHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->inlineEditingConfigurator = $this->createMock(InlineEditingConfigurator::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->extension = new TagsExtension(
            $this->tagManager,
            $this->entityClassResolver,
            $this->taggableHelper,
            $this->authorizationChecker,
            $this->tokenStorage,
            $this->inlineEditingConfigurator,
            $this->featureChecker
        );
        $this->extension->setParameters(new ParameterBag());
    }

    public function testGetPriority(): void
    {
        self::assertEquals(10, $this->extension->getPriority());
    }

    public function testVisitMetadata(): void
    {
        $config = DatagridConfiguration::create([
            'extended_entity_name' => 'Test',
            Configuration::BASE_CONFIG_KEY => ['enable' => true]
        ]);
        $data = MetadataObject::create([]);

        $this->inlineEditingConfigurator->expects(self::once())
            ->method('isInlineEditingSupported')
            ->with($config)
            ->willReturn(true);

        $this->extension->visitMetadata($config, $data);
        self::assertEquals(
            $config->offsetGet(Configuration::BASE_CONFIG_KEY),
            $data->offsetGet(Configuration::BASE_CONFIG_KEY)
        );
    }

    /**
     * @dataProvider parametersDataProvider
     */
    public function testIsApplicable(
        array $parameters,
        bool $featureEnabled,
        bool $isTaggable,
        bool $isGranted,
        ?TokenInterface $token,
        bool $expected
    ): void {
        $config = DatagridConfiguration::create($parameters);
        $config->setName('test_grid');

        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->with('manage_tags')
            ->willReturn($featureEnabled);

        $this->taggableHelper->expects(self::any())
            ->method('isTaggable')
            ->with('Test')
            ->willReturn($isTaggable);
        $this->tokenStorage->expects(self::any())
            ->method('getToken')
            ->willReturn($token);

        $this->authorizationChecker->expects(self::any())
            ->method('isGranted')
            ->with('oro_tag_view')
            ->willReturn($isGranted);

        self::assertEquals($expected, $this->extension->isApplicable($config));
    }

    public function parametersDataProvider(): array
    {
        return [
            [
                ['extended_entity_name' => 'Test', 'source' => ['type' => 'orm'], 'properties' => ['id' => 'id']],
                true,
                true,
                true,
                $this->createMock(TokenInterface::class),
                true
            ],
            [
                ['extended_entity_name' => 'Test', 'source' => ['type' => 'orm'], 'properties' => ['id' => 'id']],
                false,
                true,
                true,
                $this->createMock(TokenInterface::class),
                false
            ],
            [
                ['extended_entity_name' => 'Test', 'source' => ['type' => 'orm'], 'properties' => ['id' => 'id']],
                true,
                false,
                true,
                $this->createMock(TokenInterface::class),
                false
            ],
            [
                ['extended_entity_name' => 'Test', 'source' => ['type' => 'orm'], 'properties' => ['id' => 'id']],
                true,
                true,
                false,
                $this->createMock(TokenInterface::class),
                false
            ],
            [
                ['extended_entity_name' => 'Test', 'source' => ['type' => 'orm'], 'properties' => ['id' => 'id']],
                true,
                true,
                true,
                null,
                false
            ],
            [
                ['extended_entity_name' => null, 'source' => ['type' => 'orm'], 'properties' => ['id' => 'id']],
                true,
                true,
                true,
                $this->createMock(TokenInterface::class),
                false
            ],
            [
                ['extended_entity_name' => 'Test', 'source' => ['type' => 'array'], 'properties' => ['id' => 'id']],
                true,
                true,
                true,
                $this->createMock(TokenInterface::class),
                false
            ],
            [
                ['extended_entity_name' => 'Test', 'source' => ['type' => 'orm'], 'properties' => []],
                true,
                true,
                true,
                $this->createMock(TokenInterface::class),
                false
            ]
        ];
    }

    public function testProcessConfigs(): void
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

        $this->inlineEditingConfigurator->expects(self::once())
            ->method('isInlineEditingSupported')
            ->with($config)
            ->willReturn(true);
        $this->inlineEditingConfigurator->expects(self::once())
            ->method('configureInlineEditingForGrid')
            ->with($config);
        $this->inlineEditingConfigurator->expects(self::once())
            ->method('configureInlineEditingForColumn')
            ->with($config, 'tags');

        $this->extension->processConfigs($config);

        $actualParameters = $config->toArray();
        self::assertArrayHasKey('tags', $actualParameters['columns']);
        self::assertArrayHasKey('tagname', $actualParameters['filters']['columns']);
        self::assertTrue($actualParameters['inline_editing']['enable']);
    }
}
