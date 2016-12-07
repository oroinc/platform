<?php
namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async;

use Oro\Bundle\DataGridBundle\Async\ExportCsvMessageProcessor;
use Oro\Bundle\DataGridBundle\Async\Topics;

class ExportCsvMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::EXPORT_CSV],
            ExportCsvMessageProcessor::getSubscribedTopics()
        );
    }
}
