<?php
namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async;

use Oro\Bundle\DataGridBundle\Async\ExportXlsxMessageProcessor;
use Oro\Bundle\DataGridBundle\Async\Topics;

class ExportXlsxMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::EXPORT_XLSX],
            ExportXlsxMessageProcessor::getSubscribedTopics()
        );
    }
}
