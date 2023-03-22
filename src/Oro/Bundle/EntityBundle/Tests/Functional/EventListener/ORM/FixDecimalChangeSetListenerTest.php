<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\EventListener\ORM;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\Entity\TestDecimalEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD)
 */
class FixDecimalChangeSetListenerTest extends WebTestCase
{
    private DebugStack $logger;

    protected function setUp(): void
    {
        $this->initClient([]);
        $this->logger = new DebugStack();
        $this->getEntityManager()->getConnection()->getConfiguration()->setSQLLogger($this->logger);
    }

    public function testDecimal()
    {
        $owner = new TestDecimalEntity();
        $owner->setDecimalProperty(0.001);
        $em = $this->saveEntity($owner);

        $this->logger->queries = [];
        $owner = $this->refreshEntity($owner);
        $owner->setDecimalProperty(0.001);
        $em->flush();
        self::assertUpdateQueriesCount(0, $this->logger->queries);

        $this->logger->queries = [];
        $owner = $this->refreshEntity($owner);
        $owner->setDecimalProperty(0.002);
        $em->flush();
        self::assertUpdateQueriesCount(1, $this->logger->queries);
    }

    private function saveEntity(TestDecimalEntity $owner): EntityManagerInterface
    {
        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->flush();

        return $em;
    }

    private function refreshEntity(TestDecimalEntity $owner): TestDecimalEntity
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->find(ClassUtils::getClass($owner), $owner->getId());
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(TestDecimalEntity::class);
    }

    private static function assertUpdateQueriesCount(int $expectedCount, array $queries, string $message = '')
    {
        self::assertCount(
            $expectedCount,
            array_filter($queries, fn (array $el) => preg_match('/^\s*UPDATE/i', $el['sql'] ?? '')),
            $message
        );
    }
}
