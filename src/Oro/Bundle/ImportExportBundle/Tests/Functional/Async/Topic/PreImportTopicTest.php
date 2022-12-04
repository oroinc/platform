<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async\Topic;

use Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ReflectionUtil;

class PreImportTopicTest extends WebTestCase
{
    private function getPreImportTopic(): PreImportTopic
    {
        return self::getContainer()->get(PreImportTopic::class);
    }

    public function testIfCorrectBatchSizeBeingPassed(): void
    {
        $importTopic = $this->getPreImportTopic();
        $importBatchSize = self::getContainer()->getParameter('oro_importexport.import.size_of_batch');
        self::assertEquals(
            $importBatchSize,
            ReflectionUtil::getPropertyValue($importTopic, 'batchSize')
        );
    }
}
