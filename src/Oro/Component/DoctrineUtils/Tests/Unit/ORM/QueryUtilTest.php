<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;
use Oro\Component\TestUtils\ORM\OrmTestCase;

class QueryUtilTest extends OrmTestCase
{
    /** @var EntityManager */
    protected $em;

    public function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'Test' => 'Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity'
            ]
        );
    }

    public function testCloneQuery()
    {
        $query = new Query($this->em);
        $query->setDQL('SELECT e FROM Test:Item e WHERE e.id = :id');
        $query->setHint('hint1', 'value1');
        $query->setParameter('id', 123);

        $clonedQuery = QueryUtil::cloneQuery($query);
        self::assertNotSame($query, $clonedQuery);
        self::assertEquals($query, $clonedQuery);
    }

    public function testAddTreeWalkerWhenQueryDoesNotHaveHints()
    {
        $query = new Query($this->em);

        self::assertTrue(
            QueryUtil::addTreeWalker($query, 'Test\Walker')
        );
        self::assertEquals(
            [
                Query::HINT_CUSTOM_TREE_WALKERS => ['Test\Walker']
            ],
            $query->getHints()
        );
    }

    public function testAddTreeWalkerWhenQueryHasOtherHints()
    {
        $query = new Query($this->em);
        $query->setHint('test', 'value');

        self::assertTrue(
            QueryUtil::addTreeWalker($query, 'Test\Walker')
        );
        self::assertEquals(
            [
                'test'                          => 'value',
                Query::HINT_CUSTOM_TREE_WALKERS => ['Test\Walker']
            ],
            $query->getHints()
        );
    }

    public function testAddTreeWalkerWhenQueryHasOtherTreeWalkers()
    {
        $query = new Query($this->em);
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, ['Test\OtherWalker']);

        self::assertTrue(
            QueryUtil::addTreeWalker($query, 'Test\Walker')
        );
        self::assertEquals(
            [
                Query::HINT_CUSTOM_TREE_WALKERS => ['Test\OtherWalker', 'Test\Walker']
            ],
            $query->getHints()
        );
    }

    public function testAddTreeWalkerWhenQueryAlreadyHasTreeWalker()
    {
        $query = new Query($this->em);
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, ['Test\Walker']);

        self::assertFalse(
            QueryUtil::addTreeWalker($query, 'Test\Walker')
        );
        self::assertEquals(
            [
                Query::HINT_CUSTOM_TREE_WALKERS => ['Test\Walker']
            ],
            $query->getHints()
        );
    }
}
