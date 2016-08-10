<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\TranslationBundle\EventListener\Datagrid\LanguageListener;
use Oro\Bundle\TranslationBundle\Provider\TranslationStatisticProvider;

class LanguageListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|TranslationStatisticProvider */
    protected $provider;

    /** @var LanguageListener */
    protected $listener;

    protected function setUp()
    {
        $this->provider = $this->getMockBuilder(TranslationStatisticProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new LanguageListener($this->provider);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->provider);
    }

    public function testOnResultAfter()
    {
        $this->provider->expects($this->once())
            ->method('get')
            ->willReturn(
                [
                    [
                        'code' => 'fr_FR',
                        'realCode' => 'fr',
                        'translationStatus' => '100',
                        'lastBuildDate' => '2016-07-27T01:06:00+0000'
                    ],
                    [
                        'code' => 'ja_JP',
                        'realCode' => 'ja',
                        'translationStatus' => '50',
                        'lastBuildDate' => '2016-07-27T01:06:00+0000'
                    ],
                    [
                        'code' => 'pl_PL',
                        'realCode' => 'pl',
                        'translationStatus' => '1',
                        'lastBuildDate' => '2016-07-27T01:06:00+0000'
                    ]
                ]
            );

        $event = $this->getEvent(['nl_NL', 'ja_JP']);

        $this->listener->onResultAfter($event);

        $this->assertEquals(
            [
                new ResultRecord(['code' => 'nl_NL', 'translationCompleteness' => 0]),
                new ResultRecord(['code' => 'ja_JP', 'translationCompleteness' => 50])
            ],
            $event->getRecords()
        );
    }

    /**
     * @param array $codes
     * @return OrmResultAfter
     */
    protected function getEvent(array $codes)
    {
        $records = [];
        foreach ($codes as $code) {
            $records[] = new ResultRecord(['code' => $code]);
        }

        return new OrmResultAfter($this->getMock(DatagridInterface::class), $records);
    }
}
