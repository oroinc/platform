<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\EventListener\Datagrid\LanguageListener;
use Oro\Bundle\TranslationBundle\Helper\LanguageHelper;

class LanguageListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LanguageHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $languageHelper;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var TranslationKeyRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $translationKeyRepository;

    /** @var LanguageListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->languageHelper = $this->getMockBuilder(LanguageHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translationKeyRepository = $this->getMockBuilder(TranslationKeyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new LanguageListener(
            $this->languageHelper,
            $this->doctrineHelper
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->listener,
            $this->languageHelper,
            $this->doctrineHelper,
            $this->translationKeyRepository
        );
    }

    public function testOnBuildBefore()
    {
        $event = new BuildBefore(
            $this->createMock(DatagridInterface::class),
            DatagridConfiguration::create([
                'source' => [
                    'query' => [
                        'from'    => [
                            ['table' => 'Test\Entity', 'alias' => 'rootAlias']
                        ]
                    ]
                ]
            ])
        );

        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [
                'columns' => $this->getColumns(),
                'source' => $this->getSource('rootAlias'),
            ],
            $event->getConfig()->toArray()
        );
    }

    public function testOnBuildBeforeAndExistingDefinition()
    {
        $event = new BuildBefore(
            $this->createMock(DatagridInterface::class),
            DatagridConfiguration::create([
                'source' => [
                    'query' => [
                        'from'    => [
                            ['table' => 'Test\Entity', 'alias' => 'rootAlias']
                        ]
                    ]
                ],
                'columns' => [
                    LanguageListener::COLUMN_STATUS => [
                        'label' => 'custom_label1',
                    ],
                    LanguageListener::COLUMN_COVERAGE => [
                        'label' => 'custom_label2',
                    ],
                ],
            ])
        );

        $this->listener->onBuildBefore($event);

        $columns = $this->getColumns();
        $columns[LanguageListener::COLUMN_STATUS]['label'] = 'custom_label1';
        $columns[LanguageListener::COLUMN_COVERAGE]['label'] = 'custom_label2';

        $this->assertEquals(
            [
                'columns' => $columns,
                'source' => $this->getSource('rootAlias'),
            ],
            $event->getConfig()->toArray()
        );
    }

    public function testOnResultAfter()
    {
        $lang1 = new Language();
        $lang2 = (new Language())->setInstalledBuildDate(new \DateTime());

        $this->doctrineHelper->expects($this->any())
            ->method('getEntity')
            ->will($this->returnValueMap([
                [Language::class, 1, $lang1],
                [Language::class, 2, $lang2],
            ]));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(TranslationKey::class)
            ->willReturn($this->translationKeyRepository);

        $this->translationKeyRepository->expects($this->once())
            ->method('getCount')
            ->willReturn(100);

        $this->languageHelper->expects($this->any())
            ->method('isAvailableUpdateTranslates')
            ->will($this->returnValueMap([
                [$lang1, false],
                [$lang2, true],
            ]));

        $this->languageHelper->expects($this->any())
            ->method('isAvailableInstallTranslates')
            ->will($this->returnValueMap([
                [$lang1, true],
                [$lang2, false],
            ]));

        $event = $this->getEvent([
            ['id' => 1, LanguageListener::STATS_COUNT => null],
            ['id' => 2, LanguageListener::STATS_COUNT => 50],
        ]);

        $this->listener->onResultAfter($event);

        $this->assertEquals(
            [
                new ResultRecord([
                    'id' => 1,
                    LanguageListener::STATS_COUNT => null,
                    LanguageListener::STATS_COVERAGE_NAME => 0,
                    LanguageListener::STATS_INSTALLED => false,
                    LanguageListener::STATS_AVAILABLE_UPDATE => false,
                    LanguageListener::STATS_AVAILABLE_INSTALL => true,
                ]),
                new ResultRecord([
                    'id' => 2,
                    LanguageListener::STATS_COUNT => 50,
                    LanguageListener::STATS_COVERAGE_NAME => 0.5,
                    LanguageListener::STATS_INSTALLED => true,
                    LanguageListener::STATS_AVAILABLE_UPDATE => true,
                    LanguageListener::STATS_AVAILABLE_INSTALL => false,
                ]),
            ],
            $event->getRecords()
        );
    }

    /**
     * @param array $items
     * @return OrmResultAfter
     */
    protected function getEvent(array $items)
    {
        $records = [];
        foreach ($items as $item) {
            $records[] = new ResultRecord($item);
        }

        return new OrmResultAfter($this->createMock(DatagridInterface::class), $records);
    }

    /**
     * @return array
     */
    protected function getColumns()
    {
        return [
            LanguageListener::COLUMN_STATUS => [
                'label' => 'oro.translation.language.translation_status.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'template' => 'OroTranslationBundle:Language:Datagrid/translationStatus.html.twig',
            ],
            LanguageListener::COLUMN_COVERAGE => [
                'label' => 'oro.translation.language.translation_completeness.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'template' => 'OroTranslationBundle:Language:Datagrid/translationCompleteness.html.twig',
            ],
        ];
    }

    /**
     * @param string $rootAlias
     * @return array
     */
    protected function getSource($rootAlias)
    {
        return [
            'query' => [
                'select' => [
                    sprintf('COUNT(translation) %s', LanguageListener::STATS_COUNT),
                ],
                'from'    => [
                    ['table' => 'Test\Entity', 'alias' => $rootAlias]
                ],
                'join' => [
                    'left' => [
                        [
                            'join' => Translation::class,
                            'alias' => 'translation',
                            'conditionType' => 'WITH',
                            'condition' => sprintf('translation.language = %s', $rootAlias),
                        ]
                    ],
                ],
            ],
        ];
    }
}
