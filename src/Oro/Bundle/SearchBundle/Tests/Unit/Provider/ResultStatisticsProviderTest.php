<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Provider\ResultStatisticsProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class ResultStatisticsProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ResultStatisticsProvider
     */
    protected $target;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Indexer
     */
    protected $indexer;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager
     */
    protected $configManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->indexer = $this->createMock(Indexer::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator
            ->method('trans')
            ->willReturnCallback(function ($string) {
                return $string . ' TRANS';
            });

        $this->target = new ResultStatisticsProvider($this->indexer, $this->configManager, $this->translator);
    }

    public function testGetGroupedResultsWithNoDocumentsFound()
    {
        $searchString = 'product';

        $this->indexer
            ->expects($this->once())
            ->method('getDocumentsCountGroupByEntityFQCN')
            ->with($searchString)
            ->willReturn([]);

        $expectedResult = [
            '' => [
                'count'  => 0,
                'class'  => '',
                'icon'   => '',
                'label'  => ''
            ]
        ];

        $result = $this->target->getGroupedResultsBySearchQuery($searchString);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider getGroupedResultsBySearchQueryProvider
     */
    public function testGetGroupedResultsBySearchQuery(
        array $documentsCountToClassName,
        array $entityAliasMaps,
        array $entityConfigMaps,
        array $expectedResult
    ) {
        $searchString = 'product';

        $this->indexer
            ->expects($this->once())
            ->method('getDocumentsCountGroupByEntityFQCN')
            ->with($searchString)
            ->willReturn($documentsCountToClassName);

        $this->indexer
            ->expects($this->exactly(count($documentsCountToClassName)))
            ->method('getEntityAlias')
            ->willReturnCallback(function ($entityFQCN) use ($entityAliasMaps) {
                return $entityAliasMaps[$entityFQCN];
            });

        $this->configManager
            ->expects($this->exactly(count($documentsCountToClassName)))
            ->method('hasConfig')
            ->willReturnCallback(function ($entityFQCN) use ($entityConfigMaps) {
                return isset($entityConfigMaps[$entityFQCN]);
            });

        $this->configManager
            ->expects($this->exactly(count($entityConfigMaps)))
            ->method('getConfig')
            ->willReturnCallback(function (EntityConfigId $configId) use ($entityConfigMaps) {
                return $entityConfigMaps[$configId->getClassName()];
            });

        $result = $this->target->getGroupedResultsBySearchQuery($searchString);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function getGroupedResultsBySearchQueryProvider()
    {
        return [
            'General flow' => [
                'documentsCountToClassName' => [
                    'first class' => 3,
                    'second class' => 5,
                    'third class' => 6
                ],
                'entityAliasMaps' => [
                    'first class' => 'f_c',
                    'second class' => 's_c',
                    'third class' => 't_c'
                ],
                'entityConfigMaps' => [
                    'first class' => $this->getConfigEntity('first class', 'f_c label', 'f_c icon'),
                    'second class' => $this->getConfigEntity('second class', 's_c label', 's_c icon'),
                    'third class' => $this->getConfigEntity('third class', 't_c label', 't_c icon')
                ],
                'expectedResult' => [
                    '' => [
                        'count'  => 14,
                        'class'  => '',
                        'icon'   => '',
                        'label'  => ''
                    ],
                    'f_c' => [
                        'count'  => 3,
                        'class'  => 'first class',
                        'icon'   => 'f_c icon',
                        'label'  => 'f_c label TRANS'
                    ],
                    's_c' => [
                        'count'  => 5,
                        'class'  => 'second class',
                        'icon'   => 's_c icon',
                        'label'  => 's_c label TRANS'
                    ],
                    't_c' => [
                        'count'  => 6,
                        'class'  => 'third class',
                        'icon'   => 't_c icon',
                        'label'  => 't_c label TRANS'
                    ],
                ]
            ],
            'One class without config flow' => [
                'documentsCountToClassName' => [
                    'first class' => 3,
                    'second class' => 5,
                    'third class' => 6
                ],
                'entityAliasMaps' => [
                    'first class' => 'f_c',
                    'second class' => 's_c',
                    'third class' => 't_c'
                ],
                'entityConfigMaps' => [
                    'first class' => $this->getConfigEntity('first class', 'f_c label'),
                    'second class' => $this->getConfigEntity('second class', null, 's_c icon'),
                ],
                'expectedResult' => [
                    '' => [
                        'count'  => 14,
                        'class'  => '',
                        'icon'   => '',
                        'label'  => ''
                    ],
                    'f_c' => [
                        'count'  => 3,
                        'class'  => 'first class',
                        'icon'   => '',
                        'label'  => 'f_c label TRANS'
                    ],
                    's_c' => [
                        'count'  => 5,
                        'class'  => 'second class',
                        'icon'   => 's_c icon',
                        'label'  => ''
                    ],
                    't_c' => [
                        'count'  => 6,
                        'class'  => 'third class',
                        'icon'   => '',
                        'label'  => ''
                    ],
                ]
            ],
        ];
    }

    protected function getConfigEntity(string $fqcn, string $label = null, string $icon = null): ConfigInterface
    {
        $values = [];
        if ($label) {
            $values['plural_label'] = $label;
        }

        if ($icon) {
            $values['icon'] = $icon;
        }

        $entityConfigId = new EntityConfigId('entity', $fqcn);
        return new Config($entityConfigId, $values);
    }
}
