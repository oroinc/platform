<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Oro\Component\DoctrineUtils\ORM\Walker\PreciseOrderByWalker;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;

class PreciseOrderByWalkerTest extends OrmTestCase
{
    /** @var EntityManagerMock */
    protected $em;

    protected function setUp()
    {
        $reader = new AnnotationReader();
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

    /**
     * @dataProvider queryModificationProvider
     */
    public function testQueryModification($dql, $expectedSql)
    {
        $query = $this->em->createQuery($dql);
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [PreciseOrderByWalker::class]);

        $this->assertEquals($expectedSql, $query->getSQL());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function queryModificationProvider()
    {
        return [
            'no ORDER BY'                                                 => [
                'SELECT p.id as pId FROM Test:Person p',
                'SELECT p0_.id AS id_0 FROM Person p0_ ORDER BY p0_.id ASC',
            ],
            'ORDER BY by PK'                                              => [
                'SELECT p.id as pId FROM Test:Person p ORDER BY p.id',
                'SELECT p0_.id AS id_0 FROM Person p0_ ORDER BY p0_.id ASC',
            ],
            'ORDER BY by PK (DESC)'                                       => [
                'SELECT p.id as pId FROM Test:Person p ORDER BY p.id DESC',
                'SELECT p0_.id AS id_0 FROM Person p0_ ORDER BY p0_.id DESC',
            ],
            'ORDER BY by PK (several fields)'                             => [
                'SELECT p.id as pId FROM Test:Person p ORDER BY p.id, p.name',
                'SELECT p0_.id AS id_0 FROM Person p0_ ORDER BY p0_.id ASC, p0_.name ASC',
            ],
            'ORDER BY by not PK'                                          => [
                'SELECT p.id as pId FROM Test:Person p ORDER BY p.name',
                'SELECT p0_.id AS id_0 FROM Person p0_ ORDER BY p0_.name ASC, p0_.id ASC',
            ],
            'ORDER BY by not PK (DESC)'                                   => [
                'SELECT p.id as pId FROM Test:Person p ORDER BY p.name DESC',
                'SELECT p0_.id AS id_0 FROM Person p0_ ORDER BY p0_.name DESC, p0_.id DESC',
            ],
            'ORDER BY by expression'                                      => [
                'SELECT p.id as pId FROM Test:Person p ORDER BY CAST(p.id as string)',
                'SELECT p0_.id AS id_0 FROM Person p0_ ORDER BY CAST(p0_.id AS char) ASC, p0_.id ASC',
            ],
            'ORDER BY by expression (DESC)'                               => [
                'SELECT p.id as pId FROM Test:Person p ORDER BY CAST(p.id as string) DESC',
                'SELECT p0_.id AS id_0 FROM Person p0_ ORDER BY CAST(p0_.id AS char) DESC, p0_.id DESC',
            ],
            'ORDER BY by PK alias'                                        => [
                'SELECT p.id as pId FROM Test:Person p ORDER BY pId',
                'SELECT p0_.id AS id_0 FROM Person p0_ ORDER BY id_0 ASC',
            ],
            'ORDER BY by not PK alias'                                    => [
                'SELECT p.id as pId, p.name as pName FROM Test:Person p ORDER BY pName',
                'SELECT p0_.id AS id_0, p0_.name AS name_1 FROM Person p0_ ORDER BY name_1 ASC, p0_.id ASC',
            ],
            'ORDER BY by association PK'                                  => [
                'SELECT p.name as pName FROM Test:Person p INNER JOIN p.bestItem bi ORDER BY bi.id',
                'SELECT p0_.name AS name_0 FROM Person p0_ INNER JOIN Item i1_ ON p0_.bestItem_id = i1_.id'
                . ' ORDER BY i1_.id ASC, p0_.id ASC',
            ],
            'ORDER BY by unidirectional association PK'                   => [
                'SELECT p.name as pName FROM Test:Person p INNER JOIN Test:Item bi WITH bi = p.bestItem'
                . ' ORDER BY bi.id',
                'SELECT p0_.name AS name_0 FROM Person p0_ INNER JOIN Item i1_ ON (i1_.id = p0_.bestItem_id)'
                . ' ORDER BY i1_.id ASC, p0_.id ASC',
            ],
            'GROUP BY by PK'                                              => [
                'SELECT p.id as pId, COUNT(p.name) FROM Test:Person p GROUP BY p.id',
                'SELECT p0_.id AS id_0, COUNT(p0_.name) AS sclr_1 FROM Person p0_ GROUP BY p0_.id'
                . ' ORDER BY p0_.id ASC',
            ],
            'GROUP BY by not PK'                                          => [
                'SELECT p.name as pName, COUNT(p.id) FROM Test:Person p GROUP BY p.name',
                'SELECT p0_.name AS name_0, COUNT(p0_.id) AS sclr_1 FROM Person p0_ GROUP BY p0_.name',
            ],
            'GROUP BY by PK alias'                                        => [
                'SELECT p.id as pId, COUNT(p.name) FROM Test:Person p GROUP BY pId',
                'SELECT p0_.id AS id_0, COUNT(p0_.name) AS sclr_1 FROM Person p0_ GROUP BY p0_.id'
                . ' ORDER BY p0_.id ASC',
            ],
            'GROUP BY by not PK alias'                                    => [
                'SELECT p.name as pName, COUNT(p.id) FROM Test:Person p GROUP BY pName',
                'SELECT p0_.name AS name_0, COUNT(p0_.id) AS sclr_1 FROM Person p0_ GROUP BY p0_.name',
            ],
            'GROUP BY by association PK'                                  => [
                'SELECT p.id as pId, COUNT(p.id) FROM Test:Person p INNER JOIN p.bestItem bi GROUP BY bi.id',
                'SELECT p0_.id AS id_0, COUNT(p0_.id) AS sclr_1 FROM Person p0_'
                . ' INNER JOIN Item i1_ ON p0_.bestItem_id = i1_.id'
                . ' GROUP BY i1_.id',
            ],
            'GROUP BY by unidirectional association PK'                   => [
                'SELECT p.id as pId, COUNT(p.id) FROM Test:Person p INNER JOIN Test:Item bi WITH bi = p.bestItem'
                . ' GROUP BY bi.id',
                'SELECT p0_.id AS id_0, COUNT(p0_.id) AS sclr_1 FROM Person p0_'
                . ' INNER JOIN Item i1_ ON (i1_.id = p0_.bestItem_id)'
                . ' GROUP BY i1_.id',
            ],
            'DISTINCT, no ORDER BY, PK in SELECT'                         => [
                'SELECT DISTINCT p.id as pId FROM Test:Person p',
                'SELECT DISTINCT p0_.id AS id_0 FROM Person p0_ ORDER BY p0_.id ASC',
            ],
            'DISTINCT, no ORDER BY, no PK in SELECT'                      => [
                'SELECT DISTINCT p.name as pName FROM Test:Person p',
                'SELECT DISTINCT p0_.name AS name_0, p0_.id AS id_1 FROM Person p0_ ORDER BY p0_.id ASC',
            ],
            'DISTINCT, ORDER BY by PK, PK in SELECT'                      => [
                'SELECT DISTINCT p.id as pId FROM Test:Person p ORDER BY p.id',
                'SELECT DISTINCT p0_.id AS id_0 FROM Person p0_ ORDER BY p0_.id ASC',
            ],
            'DISTINCT, ORDER BY by PK, no PK in SELECT'                   => [
                'SELECT DISTINCT p.name as pName FROM Test:Person p ORDER BY p.id',
                'SELECT DISTINCT p0_.name AS name_0 FROM Person p0_ ORDER BY p0_.id ASC',
            ],
            'DISTINCT, ORDER BY by not PK, PK in SELECT'                  => [
                'SELECT DISTINCT p.id as pId FROM Test:Person p ORDER BY p.name',
                'SELECT DISTINCT p0_.id AS id_0 FROM Person p0_ ORDER BY p0_.name ASC, p0_.id ASC',
            ],
            'DISTINCT, ORDER BY by not PK, no PK in SELECT'               => [
                'SELECT DISTINCT p.name as pName FROM Test:Person p ORDER BY p.name',
                'SELECT DISTINCT p0_.name AS name_0, p0_.id AS id_1 FROM Person p0_'
                . ' ORDER BY p0_.name ASC, p0_.id ASC',
            ],
            'whole entity in SELECT, no ORDER BY'                         => [
                'SELECT p FROM Test:Person p',
                'SELECT p0_.id AS id_0, p0_.name AS name_1, p0_.bestItem_id AS bestItem_id_2,'
                . ' p0_.some_item AS some_item_3'
                . ' FROM Person p0_ ORDER BY p0_.id ASC',
            ],
            'whole entity in SELECT, join, no ORDER BY'                   => [
                'SELECT bi, p FROM Test:Person p INNER JOIN p.bestItem bi',
                'SELECT p0_.id AS id_0, p0_.name AS name_1, i1_.id AS id_2, i1_.name AS name_3,'
                . ' p0_.bestItem_id AS bestItem_id_4, p0_.some_item AS some_item_5, i1_.owner_id AS owner_id_6'
                . ' FROM Person p0_ INNER JOIN Item i1_ ON p0_.bestItem_id = i1_.id'
                . ' ORDER BY p0_.id ASC',
            ],
            'whole entity in SELECT, ORDER BY by PK'                      => [
                'SELECT p FROM Test:Person p ORDER BY p.id',
                'SELECT p0_.id AS id_0, p0_.name AS name_1, p0_.bestItem_id AS bestItem_id_2,'
                . ' p0_.some_item AS some_item_3 FROM Person p0_ ORDER BY p0_.id ASC',
            ],
            'whole entity in SELECT, join, ORDER BY by PK'                => [
                'SELECT bi, p FROM Test:Person p INNER JOIN p.bestItem bi ORDER BY p.id',
                'SELECT p0_.id AS id_0, p0_.name AS name_1, i1_.id AS id_2, i1_.name AS name_3,'
                . ' p0_.bestItem_id AS bestItem_id_4, p0_.some_item AS some_item_5, i1_.owner_id AS owner_id_6'
                . ' FROM Person p0_ INNER JOIN Item i1_ ON p0_.bestItem_id = i1_.id'
                . ' ORDER BY p0_.id ASC',
            ],
            'whole entity in SELECT, join, ORDER BY by join PK'           => [
                'SELECT bi, p FROM Test:Person p INNER JOIN p.bestItem bi ORDER BY bi.id',
                'SELECT p0_.id AS id_0, p0_.name AS name_1, i1_.id AS id_2, i1_.name AS name_3,'
                . ' p0_.bestItem_id AS bestItem_id_4, p0_.some_item AS some_item_5, i1_.owner_id AS owner_id_6'
                . ' FROM Person p0_ INNER JOIN Item i1_ ON p0_.bestItem_id = i1_.id'
                . ' ORDER BY i1_.id ASC, p0_.id ASC',
            ],
            'whole entity in SELECT, DISTINCT, no ORDER BY'               => [
                'SELECT DISTINCT p FROM Test:Person p',
                'SELECT DISTINCT p0_.id AS id_0, p0_.name AS name_1, p0_.bestItem_id AS bestItem_id_2,'
                . ' p0_.some_item AS some_item_3 FROM Person p0_ ORDER BY p0_.id ASC',
            ],
            'whole entity in SELECT, DISTINCT, join, no ORDER BY'         => [
                'SELECT DISTINCT bi, p FROM Test:Person p INNER JOIN p.bestItem bi',
                'SELECT DISTINCT p0_.id AS id_0, p0_.name AS name_1, i1_.id AS id_2, i1_.name AS name_3,'
                . ' p0_.bestItem_id AS bestItem_id_4, p0_.some_item AS some_item_5, i1_.owner_id AS owner_id_6'
                . ' FROM Person p0_ INNER JOIN Item i1_ ON p0_.bestItem_id = i1_.id'
                . ' ORDER BY p0_.id ASC',
            ],
            'whole entity in SELECT, DISTINCT, ORDER BY by PK'            => [
                'SELECT DISTINCT p FROM Test:Person p ORDER BY p.id',
                'SELECT DISTINCT p0_.id AS id_0, p0_.name AS name_1, p0_.bestItem_id AS bestItem_id_2,'
                . ' p0_.some_item AS some_item_3 FROM Person p0_ ORDER BY p0_.id ASC',
            ],
            'whole entity in SELECT, DISTINCT, join, ORDER BY by PK'      => [
                'SELECT DISTINCT bi, p FROM Test:Person p INNER JOIN p.bestItem bi ORDER BY p.id',
                'SELECT DISTINCT p0_.id AS id_0, p0_.name AS name_1, i1_.id AS id_2, i1_.name AS name_3,'
                . ' p0_.bestItem_id AS bestItem_id_4, p0_.some_item AS some_item_5, i1_.owner_id AS owner_id_6'
                . ' FROM Person p0_ INNER JOIN Item i1_ ON p0_.bestItem_id = i1_.id'
                . ' ORDER BY p0_.id ASC',
            ],
            'whole entity in SELECT, DISTINCT, join, ORDER BY by join PK' => [
                'SELECT DISTINCT bi, p FROM Test:Person p INNER JOIN p.bestItem bi ORDER BY bi.id',
                'SELECT DISTINCT p0_.id AS id_0, p0_.name AS name_1, i1_.id AS id_2, i1_.name AS name_3,'
                . ' p0_.bestItem_id AS bestItem_id_4, p0_.some_item AS some_item_5, i1_.owner_id AS owner_id_6'
                . ' FROM Person p0_ INNER JOIN Item i1_ ON p0_.bestItem_id = i1_.id'
                . ' ORDER BY i1_.id ASC, p0_.id ASC',
            ],
            'partial SELECT, no ORDER BY'                                 => [
                'SELECT partial p.{id} FROM Test:Person p',
                'SELECT p0_.id AS id_0, p0_.bestItem_id AS bestItem_id_1, p0_.some_item AS some_item_2'
                . ' FROM Person p0_ ORDER BY p0_.id ASC',
            ],
            'partial SELECT, ORDER BY by PK'                              => [
                'SELECT partial p.{id} FROM Test:Person p ORDER BY p.id',
                'SELECT p0_.id AS id_0, p0_.bestItem_id AS bestItem_id_1, p0_.some_item AS some_item_2'
                . ' FROM Person p0_ ORDER BY p0_.id ASC',
            ],
            'partial SELECT, DISTINCT, no ORDER BY'                       => [
                'SELECT DISTINCT partial p.{id} FROM Test:Person p',
                'SELECT DISTINCT p0_.id AS id_0, p0_.bestItem_id AS bestItem_id_1, p0_.some_item AS some_item_2'
                . ' FROM Person p0_ ORDER BY p0_.id ASC',
            ],
        ];
    }
}
