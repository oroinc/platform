<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\EventListener\Datagrid\LanguageListener;
use Oro\Bundle\TranslationBundle\Helper\LanguageHelper;

class LanguageListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var LanguageHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $languageHelper;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var LanguageListener */
    protected $listener;

    protected function setUp()
    {
        $this->languageHelper = $this->getMockBuilder(LanguageHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new LanguageListener($this->languageHelper, $this->doctrineHelper);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->languageHelper, $this->doctrineHelper);
    }

    public function testOnBuildBefore()
    {
        $event = new BuildBefore($this->getMock(DatagridInterface::class), DatagridConfiguration::create([]));

        $this->listener->onBuildBefore($event);

        $this->assertEquals(['columns' => $this->getColumns()], $event->getConfig()->toArray());
    }

    public function testOnBuildBeforeAndExistingDefinition()
    {
        $event = new BuildBefore(
            $this->getMock(DatagridInterface::class),
            DatagridConfiguration::create([
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

        $this->assertEquals(['columns' => $columns], $event->getConfig()->toArray());
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

        $this->languageHelper->expects($this->any())
            ->method('getTranslationStatus')
            ->will($this->returnValueMap([
                [$lang1, 0],
                [$lang2, 50],
            ]));

        $this->languageHelper->expects($this->any())
            ->method('isAvailableUpdateTranslates')
            ->will($this->returnValueMap([
                [$lang1, false],
                [$lang2, true],
            ]));

        $event = $this->getEvent([1, 2]);

        $this->listener->onResultAfter($event);

        $this->assertEquals(
            [
                new ResultRecord([
                    'id' => 1,
                    LanguageListener::STATS_COVERAGE_NAME => 0,
                    LanguageListener::STATS_INSTALLED => false,
                    LanguageListener::STATS_AVAILABLE_UPDATE => false,
                ]),
                new ResultRecord([
                    'id' => 2,
                    LanguageListener::STATS_COVERAGE_NAME => 50,
                    LanguageListener::STATS_INSTALLED => true,
                    LanguageListener::STATS_AVAILABLE_UPDATE => true,
                ]),
            ],
            $event->getRecords()
        );
    }

    /**
     * @param array $ids
     * @return OrmResultAfter
     */
    protected function getEvent(array $ids)
    {
        $records = [];
        foreach ($ids as $id) {
            $records[] = new ResultRecord(['id' => $id]);
        }

        return new OrmResultAfter($this->getMock(DatagridInterface::class), $records);
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
}
