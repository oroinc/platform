<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use Oro\Bundle\ApiBundle\Util\CriteriaNormalizer;
use Oro\Bundle\ApiBundle\Util\CriteriaPlaceholdersResolver;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class CriteriaConnectorTest extends OrmRelatedTestCase
{
    const ENTITY_NAMESPACE = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\\';

    /** @var Criteria */
    protected $criteria;

    /** @var CriteriaConnector */
    protected $criteriaConnector;

    protected function setUp()
    {
        parent::setUp();

        $this->criteria = new Criteria(new EntityClassResolver($this->doctrine));
        $this->criteriaConnector = new CriteriaConnector(
            new CriteriaNormalizer(),
            new CriteriaPlaceholdersResolver()
        );
    }

    /**
     * @param string $entityShortClass
     *
     * @return string
     */
    protected function getEntityClass($entityShortClass)
    {
        return self::ENTITY_NAMESPACE . $entityShortClass;
    }

    /**
     * @param $expectedDql
     */
    protected function assertQuery($expectedDql)
    {
        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from($this->getEntityClass('User'), 'e');

        $this->criteriaConnector->applyCriteria($qb, $this->criteria);

        $this->assertEquals(
            $this->sortJoins($expectedDql),
            $this->sortJoins(str_replace(self::ENTITY_NAMESPACE, 'Test:', $qb->getDQL()))
        );
    }

    /**
     * Update expected result to smooth the difference in sorting algorithm in php5 and php7
     * https://bugs.php.net/bug.php?id=69158
     *
     * @param string $dql
     *
     * @return string
     */
    protected function sortJoins($dql)
    {
        $tail = '';
        $pos = strpos($dql, ' WHERE ');
        if (false !== $pos) {
            $tail = substr($dql, $pos);
            $dql = substr($dql, 0, $pos);
        }
        if (preg_match_all('/(LEFT|INNER) JOIN /', $dql, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            $joins = [];
            for ($i = count($matches) - 1; $i >= 0; $i--) {
                $pos = $matches[$i][0][1];
                $join = substr($dql, $pos);
                if (substr($join, -1) !== ' ') {
                    $join .= ' ';
                }
                $joins[] = $join;
                $dql = substr($dql, 0, $pos);
            }
            sort($joins, SORT_STRING);
            $dql .= rtrim(implode('', $joins), ' ');
        }
        $dql .= $tail;

        return $dql;
    }

    public function testOrderBy()
    {
        $this->criteria->orderBy(['id' => Criteria::ASC, 'category.name' => Criteria::ASC]);
        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category category'
            . ' ORDER BY e.id ASC, category.name ASC'
        );
    }

    public function testWhere()
    {
        $this->criteria->andWhere(
            $this->criteria->expr()->orX(
                $this->criteria->expr()->eq('category.name', 'test_category'),
                $this->criteria->expr()->eq('groups.name', 'test_group')
            )
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE category.name = :category_name OR groups.name = :groups_name'
        );
    }

    public function testNestedFieldInOrderBy()
    {
        $this->criteria->orderBy(['id' => Criteria::ASC, 'products.category.name' => Criteria::ASC]);

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.products products'
            . ' LEFT JOIN products.category category'
            . ' ORDER BY e.id ASC, category.name ASC'
        );
    }

    public function testNestedFieldInWhere()
    {
        $this->criteria->andWhere(
            $this->criteria->expr()->eq('products.category.name', 'test_category')
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.products products'
            . ' INNER JOIN products.category category'
            . ' WHERE category.name = :category_name'
        );
    }

    public function testNestedFieldInOrderByAndJoinsAlreadyExist()
    {
        $this->criteria->addLeftJoin('products', '{root}.products')->setAlias('products');
        $this->criteria->addLeftJoin('products.category', '{products}.category')->setAlias('category');
        $this->criteria->orderBy(['products.category.name' => Criteria::ASC]);

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.products products'
            . ' LEFT JOIN products.category category'
            . ' ORDER BY category.name ASC'
        );
    }

    public function testNestedFieldInWhereAndJoinsAlreadyExist()
    {
        $this->criteria->addLeftJoin('products', '{root}.products')->setAlias('products');
        $this->criteria->addLeftJoin('products.category', '{products}.category')->setAlias('category');
        $this->criteria->andWhere(
            $this->criteria->expr()->eq('products.category.name', 'test_category')
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.products products'
            . ' INNER JOIN products.category category'
            . ' WHERE category.name = :category_name'
        );
    }

    public function testNestedFieldWithPlaceholderInWhereAndJoinsAlreadyExist()
    {
        $this->criteria->addLeftJoin('products', '{root}.products')->setAlias('products');
        $this->criteria->addLeftJoin('products.category', '{products}.category')->setAlias('category');
        $this->criteria->andWhere(
            $this->criteria->expr()->eq('{products.category}.name', 'test_category')
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.products products'
            . ' LEFT JOIN products.category category'
            . ' WHERE category.name = :category_name'
        );
    }

    public function testPlaceholdersInOrderBy()
    {
        $this->criteria->addLeftJoin('category', '{root}.category');
        $this->criteria->addLeftJoin('products', '{root}.products');
        $this->criteria->addLeftJoin('products.category', '{products}.category');
        $this->criteria->orderBy(
            [
                '{root}.name'              => Criteria::ASC,
                '{category}.name'          => Criteria::ASC,
                '{products.category}.name' => Criteria::ASC
            ]
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category alias1'
            . ' LEFT JOIN e.products alias2'
            . ' LEFT JOIN alias2.category alias3'
            . ' ORDER BY e.name ASC, alias1.name ASC, alias3.name ASC'
        );
    }

    public function testPlaceholdersInWhere()
    {
        $this->criteria->addLeftJoin('category', '{root}.category');
        $this->criteria->addLeftJoin('products', '{root}.products');
        $this->criteria->addLeftJoin('products.category', '{products}.category');
        $this->criteria->andWhere(
            $this->criteria->expr()->orX(
                $this->criteria->expr()->eq('{root}.name', 'test_user'),
                $this->criteria->expr()->eq('{category}.name', 'test_category'),
                $this->criteria->expr()->eq('{products.category}.name', 'test_category')
            )
        );

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category alias1'
            . ' LEFT JOIN e.products alias2'
            . ' LEFT JOIN alias2.category alias3'
            . ' WHERE e.name = :e_name OR alias1.name = :alias1_name OR alias3.name = :alias3_name'
        );
    }

    public function testAssociationsWithSameName()
    {
        $this->criteria->orderBy(['owner.owner.owner.name' => Criteria::ASC]);

        $this->assertQuery(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.owner owner'
            . ' LEFT JOIN owner.owner owner1'
            . ' LEFT JOIN owner1.owner owner2'
            . ' ORDER BY owner2.name ASC'
        );
    }
}
