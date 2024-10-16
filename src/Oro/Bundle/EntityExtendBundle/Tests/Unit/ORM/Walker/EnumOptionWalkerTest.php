<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\ORM\Walker;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Query;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\ORM\Walker\EnumOptionWalker;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class EnumOptionWalkerTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AttributeDriver([]));
    }

    public function testWalkerNoWhere(): void
    {
        $query = $this->em->getRepository(EnumOption::class)->createQueryBuilder('e')
            ->select('e.id')
            ->getQuery();

        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [EnumOptionWalker::class]);
        $query->setHint('oro_entity_extend.enum_option', 'test_enum');

        $this->assertEquals(
            'SELECT o0_.id AS id_0 FROM oro_enum_option o0_ WHERE o0_.enum_code = \'test_enum\'',
            $query->getSQL()
        );
    }

    public function testWalkerWithOneWhereCondition(): void
    {
        $query = $this->em->getRepository(EnumOption::class)->createQueryBuilder('e')
            ->select('e.id')
            ->where('e.id = 1')
            ->getQuery();

        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [EnumOptionWalker::class]);
        $query->setHint('oro_entity_extend.enum_option', 'test_enum');

        $this->assertEquals(
            'SELECT o0_.id AS id_0 FROM oro_enum_option o0_'
            . ' WHERE o0_.id = 1 AND o0_.enum_code = \'test_enum\'',
            $query->getSQL()
        );
    }

    public function testWalkerWithComplexWhereCondition(): void
    {
        $query = $this->em->getRepository(EnumOption::class)->createQueryBuilder('e')
            ->select('e.id')
            ->where('e.id = 1 OR e.priority = 2')
            ->getQuery();

        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [EnumOptionWalker::class]);
        $query->setHint('oro_entity_extend.enum_option', 'test_enum');

        $this->assertEquals(
            'SELECT o0_.id AS id_0 FROM oro_enum_option o0_'
            . ' WHERE (o0_.id = 1 OR o0_.priority = 2) AND o0_.enum_code = \'test_enum\'',
            $query->getSQL()
        );
    }

    public function testWalkerWithSeveralAndWhereCondition(): void
    {
        $query = $this->em->getRepository(EnumOption::class)->createQueryBuilder('d')
            ->select('d.id')
            ->where('d.id = 1 AND d.priority = 2')
            ->getQuery();

        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [EnumOptionWalker::class]);
        $query->setHint('oro_entity_extend.enum_option', 'test_enum');

        $this->assertEquals(
            'SELECT o0_.id AS id_0 FROM oro_enum_option o0_'
            . ' WHERE o0_.id = 1 AND o0_.priority = 2 AND o0_.enum_code = \'test_enum\'',
            $query->getSQL()
        );
    }
}
