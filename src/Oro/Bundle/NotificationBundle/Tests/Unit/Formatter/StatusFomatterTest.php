<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\NotificationBundle\Formatter\StatusFormatter;
use Oro\Bundle\NotificationBundle\Entity\MassNotification;

class StatusFomatterTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslatorInterface */
    protected $translation;

    /** @var StatusFormatter */
    protected $formatter;

    protected function setUp()
    {
        $this->translation = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->getMock();
        $this->translation->expects($this->any())->method('trans')
            ->will($this->returnArgument(0));

        $this->formatter = new StatusFormatter($this->translation);
    }

    protected function tearDown()
    {
        unset($this->translation);
        unset($this->formatter);
    }

    /**
     * @dataProvider formatDataProvider
     *
     * @param ResultRecord $data
     * @param string       $expectedResult
     */
    public function testFormat($data, $expectedResult)
    {
        $callback = $this->formatter->format('grid', 'key', 'node');
        $result = $callback($data);
        $this->assertEquals($result, $expectedResult);
    }

    /**
     * @return array
     */
    public function formatDataProvider()
    {
        return [
            'success status' => [
                new ResultRecord(['status' => MassNotification::STATUS_SUCCESS]),
                'oro.notification.massnotification.status.success'
            ],
            'failed status' => [
                new ResultRecord(['status' => MassNotification::STATUS_FAILED]),
                'oro.notification.massnotification.status.failed'
            ]
        ];
    }
}
