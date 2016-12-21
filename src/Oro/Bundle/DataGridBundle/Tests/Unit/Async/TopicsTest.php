<?php
namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async;

use Oro\Bundle\DataGridBundle\Async\Topics;

class TopicsTest extends \PHPUnit_Framework_TestCase
{
    public function exportFormatDataProvider()
    {
        return [
            [
                'format' => 'csv',
                'expectedTopicName' => Topics::EXPORT_CSV,
            ],
            [
                'format' => 'xlsx',
                'expectedTopicName' => Topics::EXPORT_XLSX,
            ],
        ];
    }

    /**
     * @dataProvider exportFormatDataProvider
     */
    public function testShouldReturnTopicNameByFormat($format, $expectedTopicName)
    {
        $topicName = Topics::getTopicNameByExportFormat($format);

        $this->assertEquals($expectedTopicName, $topicName);
    }
}
