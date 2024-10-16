<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor\WaitForSynchronousModeMessagesProcessed;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;

class RestJsonApiSyncUpdateListTestCase extends RestJsonApiUpdateListTestCase
{
    use MessageQueueExtension;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        WaitForSynchronousModeMessagesProcessed::setContainer(self::getContainer());
        self::clearMessageCollector();
    }

    #[\Override]
    protected function tearDown(): void
    {
        WaitForSynchronousModeMessagesProcessed::setContainer(null);
        self::clearMessageCollector();
        parent::tearDown();
    }

    protected function getEntityCounts(array $entityClasses): array
    {
        $counts = [];
        foreach ($entityClasses as $entityClass) {
            $counts[$entityClass] = $this->getEntityManager($entityClass)->getRepository($entityClass)->count([]);
        }

        return $counts;
    }
}
