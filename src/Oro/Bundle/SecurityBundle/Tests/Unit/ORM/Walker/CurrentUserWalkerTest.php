<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\ORM\Walker;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Oro\Bundle\SecurityBundle\ORM\Walker\CurrentUserWalker;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class CurrentUserWalkerTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
        $this->em->getConfiguration()->setEntityNamespaces([
            'Test' => 'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS'
        ]);
    }

    public function testWalkerWithoutParameters()
    {
        $query = $this->em->getRepository('Test:CmsAddress')->createQueryBuilder('address')
            ->select('address.id')
            ->getQuery();

        $query->setHint(
            Query::HINT_CUSTOM_TREE_WALKERS,
            [CurrentUserWalker::class]
        );

        $this->assertEquals(
            'SELECT c0_.id AS id_0'
            . ' FROM cms_addresses c0_',
            $query->getSQL()
        );
    }

    public function testWalkerNoWhere()
    {
        $query = $this->em->getRepository('Test:CmsAddress')->createQueryBuilder('address')
            ->select('address.id')
            ->getQuery();

        $query->setHint(
            Query::HINT_CUSTOM_TREE_WALKERS,
            [CurrentUserWalker::class]
        );
        $query->setHint(
            CurrentUserWalker::HINT_SECURITY_CONTEXT,
            ['user' => 123, 'organization' => 456]
        );

        $this->assertEquals(
            'SELECT c0_.id AS id_0'
            . ' FROM cms_addresses c0_'
            . ' WHERE c0_.user_id = 123 AND c0_.organization_id = 456',
            $query->getSQL()
        );
    }

    public function testWalkerWithOneWhereCondition()
    {
        $query = $this->em->getRepository('Test:CmsAddress')->createQueryBuilder('address')
            ->select('address.id')
            ->where('address.id = 1')
            ->getQuery();

        $query->setHint(
            Query::HINT_CUSTOM_TREE_WALKERS,
            [CurrentUserWalker::class]
        );
        $query->setHint(
            CurrentUserWalker::HINT_SECURITY_CONTEXT,
            ['user' => 123, 'organization' => 456]
        );

        $this->assertEquals(
            'SELECT c0_.id AS id_0'
            . ' FROM cms_addresses c0_'
            . ' WHERE c0_.id = 1 AND c0_.user_id = 123 AND c0_.organization_id = 456',
            $query->getSQL()
        );
    }

    public function testWalkerWithComplexWhereCondition()
    {
        $query = $this->em->getRepository('Test:CmsAddress')->createQueryBuilder('address')
            ->select('address.id')
            ->where('address.id = 1 OR address.country = \'us\'')
            ->getQuery();

        $query->setHint(
            Query::HINT_CUSTOM_TREE_WALKERS,
            [CurrentUserWalker::class]
        );
        $query->setHint(
            CurrentUserWalker::HINT_SECURITY_CONTEXT,
            ['user' => 123, 'organization' => 456]
        );

        $this->assertEquals(
            'SELECT c0_.id AS id_0'
            . ' FROM cms_addresses c0_'
            . ' WHERE (c0_.id = 1 OR c0_.country = \'us\')'
            . ' AND c0_.user_id = 123 AND c0_.organization_id = 456',
            $query->getSQL()
        );
    }

    public function testWalkerWithSeveralAndWhereCondition()
    {
        $query = $this->em->getRepository('Test:CmsAddress')->createQueryBuilder('address')
            ->select('address.id')
            ->where('address.id = 1 AND address.country = \'us\'')
            ->getQuery();

        $query->setHint(
            Query::HINT_CUSTOM_TREE_WALKERS,
            [CurrentUserWalker::class]
        );
        $query->setHint(
            CurrentUserWalker::HINT_SECURITY_CONTEXT,
            ['user' => 123, 'organization' => 456]
        );

        $this->assertEquals(
            'SELECT c0_.id AS id_0'
            . ' FROM cms_addresses c0_'
            . ' WHERE c0_.id = 1 AND c0_.country = \'us\''
            . ' AND c0_.user_id = 123 AND c0_.organization_id = 456',
            $query->getSQL()
        );
    }
}
