<?php

declare(strict_types=1);

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\ResultIterator;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierIterationStrategy;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\LimitIdentifierWalker;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\SelectIdentifierWalker;
use Oro\Bundle\EntityBundle\Helper\IdHelper;
use PHPUnit\Framework\TestCase;

class IdentifierIterationStrategyTest extends TestCase
{
    private IdentifierIterationStrategy $strategy;

    #[\Override]
    protected function setUp(): void
    {
        $this->strategy = new IdentifierIterationStrategy();
    }

    public function testSetDataQueryIdentifiersUsesIdsSequenceWithStringType(): void
    {
        $identifiers = [10, 20, 30];

        $query = $this->getQuery();

        $this->strategy->setDataQueryIdentifiers($query, $identifiers);

        $parameter = $query->getParameters()->get(0);

        self::assertEquals(IdHelper::getIdsSequence($identifiers), $parameter->getValue());
        self::assertEquals(LimitIdentifierWalker::PARAMETER_IDS, $parameter->getName());
    }

    public function testSetDataQueryIdentifiersWithEmptyArray(): void
    {
        $identifiers = [];
        $query = $this->getQuery();

        $this->strategy->setDataQueryIdentifiers($query, $identifiers);

        $parameter = $query->getParameters()->get(0);

        self::assertEquals(IdHelper::getIdsSequence($identifiers), $parameter->getValue());
        self::assertEquals(LimitIdentifierWalker::PARAMETER_IDS, $parameter->getName());
    }

    public function testSetDataQueryIdentifiersWithSingleId(): void
    {
        $identifiers = [42];
        $query = $this->getQuery();

        $this->strategy->setDataQueryIdentifiers($query, $identifiers);

        $parameter = $query->getParameters()->get(0);

        self::assertEquals(IdHelper::getIdsSequence($identifiers), $parameter->getValue());
        self::assertEquals(LimitIdentifierWalker::PARAMETER_IDS, $parameter->getName());
    }

    public function testInitializeDataQueryAddsTreeWalkerAndResetsLimitOffset(): void
    {
        $query = $this->getQuery();

        $this->strategy->initializeDataQuery($query);

        self::assertEquals([LimitIdentifierWalker::class], $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS));
    }

    public function testInitializeIdentityQuerySetsCustomHydrationMode(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())
            ->method('addCustomHydrationMode')
            ->with('IdentifierHydrator', $this->isType('string'));

        $query = $this->getQuery($configuration);

        $this->strategy->initializeIdentityQuery($query);

        self::assertEquals([SelectIdentifierWalker::class], $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS));
    }

    private function getQuery(?Configuration $configuration = null): Query
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($configuration ?? new Configuration());

        return new Query($em);
    }
}
