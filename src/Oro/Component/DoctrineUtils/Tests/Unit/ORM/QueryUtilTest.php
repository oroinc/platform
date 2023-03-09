<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;
use Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Item;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class QueryUtilTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
    }

    public function testCloneQuery(): void
    {
        $query = new Query($this->em);
        $query->setDQL('SELECT e FROM Test:Item e WHERE e.id = :id');
        $query->setHint('hint1', 'value1');
        $query->setParameter('id', 123);

        $clonedQuery = QueryUtil::cloneQuery($query);
        self::assertNotSame($query, $clonedQuery);
        self::assertEquals($query, $clonedQuery);
    }

    public function testAddTreeWalkerWhenQueryDoesNotHaveHints(): void
    {
        $query = new Query($this->em);

        self::assertTrue(
            QueryUtil::addTreeWalker($query, 'Test\Walker')
        );
        self::assertEquals(
            [
                Query::HINT_CUSTOM_TREE_WALKERS => ['Test\Walker'],
            ],
            $query->getHints()
        );
    }

    public function testAddTreeWalkerWhenQueryHasOtherHints(): void
    {
        $query = new Query($this->em);
        $query->setHint('test', 'value');

        self::assertTrue(
            QueryUtil::addTreeWalker($query, 'Test\Walker')
        );
        self::assertEquals(
            [
                'test' => 'value',
                Query::HINT_CUSTOM_TREE_WALKERS => ['Test\Walker'],
            ],
            $query->getHints()
        );
    }

    public function testAddTreeWalkerWhenQueryHasOtherTreeWalkers(): void
    {
        $query = new Query($this->em);
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, ['Test\OtherWalker']);

        self::assertTrue(
            QueryUtil::addTreeWalker($query, 'Test\Walker')
        );
        self::assertEquals(
            [
                Query::HINT_CUSTOM_TREE_WALKERS => ['Test\OtherWalker', 'Test\Walker'],
            ],
            $query->getHints()
        );
    }

    public function testAddTreeWalkerWhenQueryAlreadyHasTreeWalker(): void
    {
        $query = new Query($this->em);
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, ['Test\Walker']);

        self::assertFalse(
            QueryUtil::addTreeWalker($query, 'Test\Walker')
        );
        self::assertEquals(
            [
                Query::HINT_CUSTOM_TREE_WALKERS => ['Test\Walker'],
            ],
            $query->getHints()
        );
    }

    public function testResetParameters(): void
    {
        $query = $this->em->createQuery('SELECT e FROM ' . Item::class . ' e WHERE e.id = :id');
        $query->setParameter('id', 42, Types::INTEGER);

        $parserResult = QueryUtil::parseQuery($query);

        self::assertNotEmpty($query->getParameters()->toArray());
        self::assertNotEmpty($parserResult->getParameterMappings());

        QueryUtil::resetParameters($query);

        self::assertEmpty($query->getParameters()->toArray());
        self::assertEmpty($parserResult->getParameterMappings());
    }

    public function testResetParametersWhenParserResult(): void
    {
        $query = $this->em->createQuery('SELECT e FROM ' . Item::class . ' e WHERE e.id = :id');
        $query->setParameter('id', 42, Types::INTEGER);

        $parserResult = QueryUtil::parseQuery($query);
        $anotherParserResult = clone $parserResult;

        self::assertNotEmpty($query->getParameters()->toArray());
        self::assertNotEmpty($parserResult->getParameterMappings());
        self::assertNotEmpty($anotherParserResult->getParameterMappings());

        QueryUtil::resetParameters($query, $anotherParserResult);

        self::assertEmpty($query->getParameters()->toArray());
        self::assertNotEmpty($parserResult->getParameterMappings());
        self::assertEmpty($anotherParserResult->getParameterMappings());
    }
}
