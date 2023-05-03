<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Functional\EventListener\ORM;

use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CurrencyBundle\Tests\Functional\Environment\Entity\TestMoneyEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class FixMoneyChangeSetListenerTest extends WebTestCase
{
    private DebugStack $logger;

    protected function setUp(): void
    {
        $this->initClient([]);
        $this->logger = new DebugStack();
        $this->getEntityManager()->getConnection()->getConfiguration()->setSQLLogger($this->logger);
    }

    public function testMoney()
    {
        $owner = new TestMoneyEntity();
        $owner->setMoneyProperty(0.001);
        $em = $this->saveEntity($owner);

        $this->logger->queries = [];
        $owner = $this->refreshEntity($owner);
        $owner->setMoneyProperty(0.001);
        $em->flush();
        self::assertUpdateQueriesCount(0, $this->logger->queries);

        $this->logger->queries = [];
        $owner = $this->refreshEntity($owner);
        $owner->setMoneyProperty(0.002);
        $em->flush();
        self::assertUpdateQueriesCount(1, $this->logger->queries);
    }

    public function testMoneyValue()
    {
        $owner = new TestMoneyEntity();
        $owner->setMoneyValueProperty('0.001');
        $em = $this->saveEntity($owner);

        $this->logger->queries = [];
        $owner = $this->refreshEntity($owner);
        $owner->setMoneyValueProperty(0.001);
        $em->flush();
        self::assertUpdateQueriesCount(0, $this->logger->queries);

        $this->logger->queries = [];
        $owner = $this->refreshEntity($owner);
        $owner->setMoneyValueProperty('0.002');
        $em->flush();
        self::assertUpdateQueriesCount(1, $this->logger->queries);
    }

    private function saveEntity(TestMoneyEntity $entity): EntityManagerInterface
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        return $em;
    }

    private function refreshEntity(TestMoneyEntity $entity): TestMoneyEntity
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->find(TestMoneyEntity::class, $entity->getId());
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(TestMoneyEntity::class);
    }

    private static function assertUpdateQueriesCount(int $expectedCount, array $queries, string $message = ''): void
    {
        self::assertCount(
            $expectedCount,
            array_filter($queries, fn (array $el) => preg_match('/^\s*UPDATE/i', $el['sql'] ?? '')),
            $message
        );
    }
}
