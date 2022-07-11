<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Provider\SearchResultProvider;
use Oro\Bundle\SearchBundle\Query\Result as SearchResult;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SearchResultProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var Indexer|\PHPUnit\Framework\MockObject\MockObject */
    private $indexer;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var SearchResultProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->indexer = $this->createMock(Indexer::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($string) {
                return $string . ' TRANS';
            });

        $this->provider = new SearchResultProvider(
            $this->indexer,
            $this->featureChecker,
            $this->configManager,
            $this->dispatcher,
            $translator
        );
    }

    public function testGetAllowedEntities(): void
    {
        $this->indexer->expects(self::once())
            ->method('getAllowedEntitiesListAliases')
            ->willReturn([
                'Test\Entity1' => 'entity_1',
                'Test\Entity2' => 'entity_2'
            ]);
        $this->featureChecker->expects(self::exactly(2))
            ->method('isResourceEnabled')
            ->willReturnMap([
                ['Test\Entity1', 'entities', null, false],
                ['Test\Entity2', 'entities', null, true],
            ]);

        self::assertEquals(
            [
                'Test\Entity2' => 'entity_2'
            ],
            $this->provider->getAllowedEntities()
        );
    }

    public function testGetSuggestions(): void
    {
        $searchString = 'product';
        $result = [
            'records_count'   => 0,
            'data'            => [],
            'count'           => 0,
            'aggregated_data' => []
        ];

        $searchResultItem = new SearchResultItem('test product');
        $searchResult = $this->createMock(SearchResult::class);
        $searchResult->expects(self::once())
            ->method('getElements')
            ->willReturn([$searchResultItem]);
        $searchResult->expects(self::once())
            ->method('toSearchResultData')
            ->willReturn($result);

        $this->indexer->expects(self::once())
            ->method('getAllowedEntitiesListAliases')
            ->willReturn([
                'Test\Entity1' => 'entity_1',
                'Test\Entity2' => 'entity_2'
            ]);
        $this->featureChecker->expects(self::exactly(2))
            ->method('isResourceEnabled')
            ->willReturnMap([
                ['Test\Entity1', 'entities', null, false],
                ['Test\Entity2', 'entities', null, true],
            ]);

        $this->indexer->expects(self::once())
            ->method('simpleSearch')
            ->with($searchString, 0, 0, ['entity_2'])
            ->willReturn($searchResult);
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new PrepareResultItemEvent($searchResultItem), PrepareResultItemEvent::EVENT_NAME);

        self::assertEquals($result, $this->provider->getSuggestions($searchString));
    }

    public function testGetSuggestionsWithFrom(): void
    {
        $searchString = 'product';
        $from = 'entity_1';
        $offset = 1;
        $maxResults = 2;
        $result = [
            'records_count'   => 0,
            'data'            => [],
            'count'           => 0,
            'aggregated_data' => []
        ];

        $searchResultItem = new SearchResultItem('test product');
        $searchResult = $this->createMock(SearchResult::class);
        $searchResult->expects(self::once())
            ->method('getElements')
            ->willReturn([$searchResultItem]);
        $searchResult->expects(self::once())
            ->method('toSearchResultData')
            ->willReturn($result);

        $this->indexer->expects(self::once())
            ->method('simpleSearch')
            ->with($searchString, $offset, $maxResults, $from)
            ->willReturn($searchResult);
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new PrepareResultItemEvent($searchResultItem), PrepareResultItemEvent::EVENT_NAME);

        self::assertEquals($result, $this->provider->getSuggestions($searchString, $from, $offset, $maxResults));
    }

    public function testGetGroupedResultsWithNoDocumentsFound(): void
    {
        $searchString = 'product';

        $this->indexer->expects(self::once())
            ->method('getAllowedEntitiesListAliases')
            ->willReturn([
                'Test\Entity1' => 'entity_1',
                'Test\Entity2' => 'entity_2'
            ]);
        $this->indexer->expects(self::once())
            ->method('getDocumentsCountGroupByEntityFQCN')
            ->with($searchString)
            ->willReturn([]);
        $this->featureChecker->expects(self::any())
            ->method('isResourceEnabled')
            ->willReturn(true);

        $expectedResult = [
            '' => [
                'count' => 0,
                'class' => '',
                'icon'  => '',
                'label' => ''
            ]
        ];

        $result = $this->provider->getGroupedResultsBySearchQuery($searchString);

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider getGroupedResultsBySearchQueryProvider
     */
    public function testGetGroupedResultsBySearchQuery(
        array $documentsCountToClassName,
        array $entityAliasMaps,
        array $entityConfigMaps,
        array $expectedResult,
        ?array $allowedEntities = null
    ): void {
        $searchString = 'product';

        $allowedEntitiesCount = 0;
        $allowedEntitiesMap = [];
        $allowedEntityAliases = [];
        $entityAliasesToSearch = [];
        if (null === $allowedEntities) {
            $allowedEntities = array_fill_keys(array_keys($documentsCountToClassName), true);
        }
        foreach ($allowedEntities as $entityClass => $allowed) {
            $entityAlias = strtolower(str_replace('\\', '_', $entityClass));
            if ($allowed) {
                $allowedEntitiesCount++;
                $entityAliasesToSearch[] = $entityAlias;
            }
            $allowedEntitiesMap[] = [$entityClass, 'entities', null, $allowed];
            $allowedEntityAliases[$entityClass] = $entityAlias;
        }

        $this->indexer->expects(self::once())
            ->method('getAllowedEntitiesListAliases')
            ->willReturn($allowedEntityAliases);
        $this->indexer->expects(self::once())
            ->method('getDocumentsCountGroupByEntityFQCN')
            ->with($searchString, $entityAliasesToSearch)
            ->willReturn($documentsCountToClassName);
        $this->featureChecker->expects(self::exactly(count($allowedEntitiesMap)))
            ->method('isResourceEnabled')
            ->willReturnMap($allowedEntitiesMap);

        $this->indexer->expects(self::exactly($allowedEntitiesCount))
            ->method('getEntityAlias')
            ->willReturnCallback(function ($entityClass) use ($entityAliasMaps) {
                return $entityAliasMaps[$entityClass];
            });

        $this->configManager->expects(self::exactly($allowedEntitiesCount))
            ->method('hasConfig')
            ->willReturnCallback(function ($entityClass) use ($entityConfigMaps) {
                return isset($entityConfigMaps[$entityClass]);
            });

        $this->configManager->expects(self::exactly(count($entityConfigMaps)))
            ->method('getConfig')
            ->willReturnCallback(function (EntityConfigId $configId) use ($entityConfigMaps) {
                return $entityConfigMaps[$configId->getClassName()];
            });

        $result = $this->provider->getGroupedResultsBySearchQuery($searchString);

        self::assertEquals($expectedResult, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getGroupedResultsBySearchQueryProvider(): array
    {
        return [
            'General flow'                  => [
                'documentsCountToClassName' => [
                    'Test\Entity1' => 3,
                    'Test\Entity2' => 5,
                    'Test\Entity3' => 6
                ],
                'entityAliasMaps'           => [
                    'Test\Entity1' => 'f_c',
                    'Test\Entity2' => 's_c',
                    'Test\Entity3' => 't_c'
                ],
                'entityConfigMaps'          => [
                    'Test\Entity1' => $this->getConfigEntity('Test\Entity1', 'f_c label', 'f_c icon'),
                    'Test\Entity2' => $this->getConfigEntity('Test\Entity2', 's_c label', 's_c icon'),
                    'Test\Entity3' => $this->getConfigEntity('Test\Entity3', 't_c label', 't_c icon')
                ],
                'expectedResult'            => [
                    ''    => [
                        'count' => 14,
                        'class' => '',
                        'icon'  => '',
                        'label' => ''
                    ],
                    'f_c' => [
                        'count' => 3,
                        'class' => 'Test\Entity1',
                        'icon'  => 'f_c icon',
                        'label' => 'f_c label TRANS'
                    ],
                    's_c' => [
                        'count' => 5,
                        'class' => 'Test\Entity2',
                        'icon'  => 's_c icon',
                        'label' => 's_c label TRANS'
                    ],
                    't_c' => [
                        'count' => 6,
                        'class' => 'Test\Entity3',
                        'icon'  => 't_c icon',
                        'label' => 't_c label TRANS'
                    ],
                ]
            ],
            'One class without config flow' => [
                'documentsCountToClassName' => [
                    'Test\Entity1' => 3,
                    'Test\Entity2' => 5,
                    'Test\Entity3' => 6
                ],
                'entityAliasMaps'           => [
                    'Test\Entity1' => 'f_c',
                    'Test\Entity2' => 's_c',
                    'Test\Entity3' => 't_c'
                ],
                'entityConfigMaps'          => [
                    'Test\Entity1' => $this->getConfigEntity('Test\Entity1', 'f_c label'),
                    'Test\Entity2' => $this->getConfigEntity('Test\Entity2', null, 's_c icon'),
                ],
                'expectedResult'            => [
                    ''    => [
                        'count' => 14,
                        'class' => '',
                        'icon'  => '',
                        'label' => ''
                    ],
                    'f_c' => [
                        'count' => 3,
                        'class' => 'Test\Entity1',
                        'icon'  => '',
                        'label' => 'f_c label TRANS'
                    ],
                    's_c' => [
                        'count' => 5,
                        'class' => 'Test\Entity2',
                        'icon'  => 's_c icon',
                        'label' => ''
                    ],
                    't_c' => [
                        'count' => 6,
                        'class' => 'Test\Entity3',
                        'icon'  => '',
                        'label' => ''
                    ],
                ]
            ],
            'With not allowed entities'     => [
                'documentsCountToClassName' => [
                    'Test\Entity2' => 5
                ],
                'entityAliasMaps'           => [
                    'Test\Entity2' => 's_c'
                ],
                'entityConfigMaps'          => [
                    'Test\Entity2' => $this->getConfigEntity('Test\Entity2', 's_c label', 's_c icon')
                ],
                'expectedResult'            => [
                    ''    => [
                        'count' => 5,
                        'class' => '',
                        'icon'  => '',
                        'label' => ''
                    ],
                    's_c' => [
                        'count' => 5,
                        'class' => 'Test\Entity2',
                        'icon'  => 's_c icon',
                        'label' => 's_c label TRANS'
                    ],
                ],
                'allowedEntities'           => [
                    'Test\Entity1' => false,
                    'Test\Entity2' => true
                ]
            ]
        ];
    }

    private function getConfigEntity(string $fqcn, string $label = null, string $icon = null): ConfigInterface
    {
        $values = [];
        if ($label) {
            $values['plural_label'] = $label;
        }
        if ($icon) {
            $values['icon'] = $icon;
        }

        return new Config(new EntityConfigId('entity', $fqcn), $values);
    }
}
