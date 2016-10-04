<?php
namespace Oro\Bundle\SearchBundle\Tests\Functional\Async;

use Oro\Bundle\SearchBundle\Async\ReindexEntityMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ReindexEntityMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_search.async.message_processor.reindex_entity');

        $this->assertInstanceOf(ReindexEntityMessageProcessor::class, $instance);
    }
}
