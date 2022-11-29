<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async\Topic;

use Oro\Bundle\ImportExportBundle\Async\Topic\ImportTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ReflectionUtil;

class ImportTopicTest extends WebTestCase
{
    private function getImportTopic(): ImportTopic
    {
        return self::getContainer()->get(ImportTopic::class);
    }

    public function testIfCorrectBatchSizeBeingPassed(): void
    {
        $importTopic = $this->getImportTopic();
        $importBatchSize = self::getContainer()->getParameter('oro_importexport.import.size_of_batch');
        self::assertEquals(
            $importBatchSize,
            ReflectionUtil::getPropertyValue($importTopic, 'batchSize')
        );
    }
}
