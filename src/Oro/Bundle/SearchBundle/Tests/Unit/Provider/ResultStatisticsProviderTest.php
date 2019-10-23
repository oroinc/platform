<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Exception\UnsupportedStatisticInterfaceEngineException;
use Oro\Bundle\SearchBundle\Provider\ResultStatisticsProvider;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @var \PHPUnit\Framework\MockObject\MockObject|ObjectMapper
     */
    protected $mapper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $search;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->indexer = $this->createMock(Indexer::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->mapper = $this->createMock(ObjectMapper::class);
        $this->search = $this->createMock(Result::class);

        $this->indexer->expects($this->any())
            ->method('simpleSearch')
            ->will($this->returnValue($this->search));

        $this->translator
            ->method('trans')
            ->willReturnCallback(function ($string) {
                return $string . ' TRANS';
            });

        $this->mapper
            ->method('getEntityConfig')
            ->willReturnCallback(function ($entity) {
                return [$entity];
            });

        $this->target = new ResultStatisticsProvider($this->indexer, $this->configManager, $this->translator);
        $this->target->setMapper($this->mapper);
    }

    public function testGetResults()
    {
        $query = 'test query';
        $this->indexer->expects($this->once())->method('simpleSearch')->with($query);
        $this->target->getResults($query);
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
                'config' => [],
                'icon'   => '',
                'label'  => ''
            ]
        ];

        $result = $this->target->getGroupedResults($searchString);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider getGroupedResultsProvider
     *
     * @param array $documentsCountToClassName
     * @param array $entityAliasMaps
     * @param array $entityConfigMaps
     * @param array $expectedResult
     */
    public function testGetGroupedResults(
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
            ->willReturnCallback(function (EntityConfigId $entityConfigId) use ($entityConfigMaps) {
                return $entityConfigMaps[$entityConfigId->getClassName()];
            });

        $result = $this->target->getGroupedResults($searchString);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function getGroupedResultsProvider()
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
                        'config' => [],
                        'icon'   => '',
                        'label'  => ''
                    ],
                    'f_c' => [
                        'count'  => 3,
                        'class'  => 'first class',
                        'config' => ['first class'],
                        'icon'   => 'f_c icon',
                        'label'  => 'f_c label TRANS'
                    ],
                    's_c' => [
                        'count'  => 5,
                        'class'  => 'second class',
                        'config' => ['second class'],
                        'icon'   => 's_c icon',
                        'label'  => 's_c label TRANS'
                    ],
                    't_c' => [
                        'count'  => 6,
                        'class'  => 'third class',
                        'config' => ['third class'],
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
                        'config' => [],
                        'icon'   => '',
                        'label'  => ''
                    ],
                    'f_c' => [
                        'count'  => 3,
                        'class'  => 'first class',
                        'config' => ['first class'],
                        'icon'   => '',
                        'label'  => 'f_c label TRANS'
                    ],
                    's_c' => [
                        'count'  => 5,
                        'class'  => 'second class',
                        'config' => ['second class'],
                        'icon'   => 's_c icon',
                        'label'  => ''
                    ],
                    't_c' => [
                        'count'  => 6,
                        'class'  => 'third class',
                        'config' => ['third class'],
                        'icon'   => '',
                        'label'  => ''
                    ],
                ]
            ],
        ];
    }

    public function testGetGroupedResultsOld()
    {
        $expectedString = 'expected';

        $firstClass = 'firstClass';
        $firstConfig = array('alias' => $firstClass);
        $firstLabel = 'first label';
        $firstIcon = 'first icon';

        $this->indexer
            ->expects($this->once())
            ->method('getDocumentsCountGroupByEntityFQCN')
            ->with($expectedString)
            ->willThrowException(new UnsupportedStatisticInterfaceEngineException());

        $firstConfigEntity = $this->getConfigEntity($firstClass, $firstLabel, $firstIcon);
        $first = $this->getSearchResultEntity($firstConfig, $firstClass);

        $secondClass = 'secondClass';
        $secondConfig = array('alias' => $secondClass);
        $secondLabel = 'second label';
        $secondIcon = 'second icon';
        $second = $this->getSearchResultEntity($secondConfig, $secondClass);
        $secondConfigEntity = $this->getConfigEntity($secondClass, $secondLabel, $secondIcon);
        $map = array($firstClass => $firstConfigEntity, $secondClass => $secondConfigEntity);

        $this->configManager
            ->expects($this->exactly(2))
            ->method('hasConfig')
            ->willReturn(true);

        $this->configManager
            ->expects($this->exactly(2))
            ->method('getConfig')
            ->will(
                $this->returnCallback(
                    function (EntityConfigId $entityConfigId) use ($map) {
                        return $map[$entityConfigId->getClassName()];
                    }
                )
            );

        $elements = array($second, $first, $first);

        $expected = array(
            ''           => array(
                'count'  => 3,
                'class'  => '',
                'config' => array(),
                'label'  => '',
                'icon'   => ''
            ),
            $firstClass  => array(
                'count'  => 2,
                'class'  => $firstClass,
                'config' => $firstConfig,
                'label'  => $firstLabel . ' TRANS',
                'icon'   => $firstIcon
            ),
            $secondClass => array(
                'count'  => 1,
                'class'  => $secondClass,
                'config' => $secondConfig,
                'label'  => $secondLabel . ' TRANS',
                'icon'   => $secondIcon
            )
        );

        $this->search->expects($this->once())
            ->method('getElements')
            ->will($this->returnValue($elements));

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->will($this->returnArgument(0));
        $actual = $this->target->getGroupedResults($expectedString);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @param array $config
     * @param string $class
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getSearchResultEntity(array $config, $class)
    {
        $entity = $this->createMock(Item::class);
        $entity->expects($this->any())
            ->method('getEntityConfig')
            ->will($this->returnValue($config));
        $entity->expects($this->any())
            ->method('getEntityName')
            ->will($this->returnValue($class));

        return $entity;
    }

    /**
     * @param string      $fqcn
     * @param string|null $label
     * @param string|null $icon
     *
     * @return ConfigInterface
     */
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
