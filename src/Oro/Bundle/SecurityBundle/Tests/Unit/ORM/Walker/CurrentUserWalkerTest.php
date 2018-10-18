<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\ORM\Walker;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Oro\Bundle\SecurityBundle\ORM\Walker\CurrentUserWalker;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;

class CurrentUserWalkerTest extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    protected function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'Test' => 'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS'
            ]
        );
    }

    public function testWalkerWithoutParameters()
    {
        $query = $this->em->getRepository('Test:CmsAddress')->createQueryBuilder('address')
            ->select('address.id')
            ->getQuery();

        $query->setHint(
            Query::HINT_CUSTOM_TREE_WALKERS,
            ['Oro\Bundle\SecurityBundle\ORM\Walker\CurrentUserWalker']
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
            ['Oro\Bundle\SecurityBundle\ORM\Walker\CurrentUserWalker']
        );
        $query->setHint(
            CurrentUserWalker::HINT_SECURITY_CONTEXT,
            ['user' => 123, 'organization' => 456]
        );

        $this->assertEquals(
            'SELECT c0_.id AS id_0'
            . ' FROM cms_addresses c0_'
            . ' WHERE c0_.user_id = 123 AND c0_.organization = 456',
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
            ['Oro\Bundle\SecurityBundle\ORM\Walker\CurrentUserWalker']
        );
        $query->setHint(
            CurrentUserWalker::HINT_SECURITY_CONTEXT,
            ['user' => 123, 'organization' => 456]
        );

        $this->assertEquals(
            'SELECT c0_.id AS id_0'
            . ' FROM cms_addresses c0_'
            . ' WHERE c0_.id = 1 AND c0_.user_id = 123 AND c0_.organization = 456',
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
            ['Oro\Bundle\SecurityBundle\ORM\Walker\CurrentUserWalker']
        );
        $query->setHint(
            CurrentUserWalker::HINT_SECURITY_CONTEXT,
            ['user' => 123, 'organization' => 456]
        );

        $this->assertEquals(
            'SELECT c0_.id AS id_0'
            . ' FROM cms_addresses c0_'
            . ' WHERE (c0_.id = 1 OR c0_.country = \'us\')'
            . ' AND c0_.user_id = 123 AND c0_.organization = 456',
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
            ['Oro\Bundle\SecurityBundle\ORM\Walker\CurrentUserWalker']
        );
        $query->setHint(
            CurrentUserWalker::HINT_SECURITY_CONTEXT,
            ['user' => 123, 'organization' => 456]
        );

        $this->assertEquals(
            'SELECT c0_.id AS id_0'
            . ' FROM cms_addresses c0_'
            . ' WHERE c0_.id = 1 AND c0_.country = \'us\''
            . ' AND c0_.user_id = 123 AND c0_.organization = 456',
            $query->getSQL()
        );
    }
}
