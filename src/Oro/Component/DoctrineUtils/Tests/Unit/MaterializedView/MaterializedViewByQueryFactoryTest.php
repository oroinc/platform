<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\MaterializedView;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Component\DoctrineUtils\MaterializedView\MaterializedViewByQueryFactory;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;
use Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Item;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class MaterializedViewByQueryFactoryTest extends OrmTestCase
{
    private EntityManagerInterface $em;
    private MaterializedViewByQueryFactory $factory;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $this->factory = new MaterializedViewByQueryFactory();
    }

    public function testCreateByQuery(): void
    {
        $query = $this->em->createQuery('SELECT e.id, e.name FROM ' . Item::class . ' e WHERE e.id = :id');
        $query
            ->setFirstResult(42)
            ->setMaxResults(142)
            ->setParameter('id', 42, Types::INTEGER);

        $name = 'sample_name';
        $withData = true;

        $materializedViewModel = $this->factory->createByQuery($query, $name, $withData);

        self::assertEquals($name, $materializedViewModel->getName());
        self::assertEquals($withData, $materializedViewModel->isWithData());
        self::assertEquals(
            'SELECT i0_.id AS id_0, i0_.name AS name_1 FROM Item i0_ WHERE i0_.id = 42',
            $materializedViewModel->getDefinition()
        );

        // Checks that original query is not affected.
        self::assertEquals(42, $query->getFirstResult());
        self::assertEquals(142, $query->getMaxResults());
        self::assertEquals(
            'SELECT i0_.id AS id_0, i0_.name AS name_1 FROM Item i0_ WHERE i0_.id = 42 LIMIT 142 OFFSET 42',
            QueryUtil::getExecutableSql($query)
        );
    }
}
