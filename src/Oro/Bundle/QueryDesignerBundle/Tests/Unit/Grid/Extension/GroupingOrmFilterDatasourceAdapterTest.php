<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid\Extension;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\QueryDesignerBundle\Grid\Extension\GroupingOrmFilterDatasourceAdapter;
use Oro\Component\TestUtils\ORM\OrmTestCase;

class GroupingOrmFilterDatasourceAdapterTest extends OrmTestCase
{
    public function testNoRestrictions()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.id'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->where('u.id = 0');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.id FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u WHERE u.id = 0',
            $qb->getDQL()
        );
    }

    public function testOneRestriction()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.id'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->where('u.id = 0');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        $ds->addRestriction($qb->expr()->eq('u.name', '1'), FilterUtility::CONDITION_AND);
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.id FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'WHERE u.id = 0 AND u.name = 1',
            $qb->getDQL()
        );
    }

    public function testOneComputedRestriction()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.status, COUNT(u.id)'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->groupBy('u.status');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '1'), FilterUtility::CONDITION_AND, true);
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.status, COUNT(u.id) FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'GROUP BY u.status '
            . 'HAVING COUNT(u.id) = 1',
            $qb->getDQL()
        );
    }

    public function testSeveralRestrictions()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.id'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->where('u.id = 0');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        $ds->addRestriction($qb->expr()->eq('u.name', '1'), FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('u.name', '2'), FilterUtility::CONDITION_OR);
        $ds->addRestriction($qb->expr()->eq('u.name', '3'), FilterUtility::CONDITION_AND);
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.id FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'WHERE u.id = 0 AND '
            . '((u.name = 1 OR u.name = 2) AND u.name = 3)',
            $qb->getDQL()
        );
    }

    public function testSeveralComputedRestrictions()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.status, COUNT(u.id)'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->groupBy('u.status');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '1'), FilterUtility::CONDITION_AND, true);
        $ds->addRestriction($qb->expr()->eq('MIN(u.id)', '2'), FilterUtility::CONDITION_OR, true);
        $ds->addRestriction($qb->expr()->eq('MAX(u.id)', '3'), FilterUtility::CONDITION_AND, true);
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.status, COUNT(u.id) FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'GROUP BY u.status '
            . 'HAVING (COUNT(u.id) = 1 OR MIN(u.id) = 2) AND MAX(u.id) = 3',
            $qb->getDQL()
        );
    }

    public function testEmptyGroup()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.id'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->where('u.id = 0');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->endRestrictionGroup();
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.id FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'WHERE u.id = 0',
            $qb->getDQL()
        );
    }

    public function testOneRestrictionInGroup()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.id'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->where('u.id = 0');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('u.name', '1'), FilterUtility::CONDITION_AND);
        $ds->endRestrictionGroup();
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.id FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'WHERE u.id = 0 AND u.name = 1',
            $qb->getDQL()
        );
    }

    public function testOneComputedRestrictionInGroup()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.status, COUNT(u.id)'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->groupBy('u.status');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '1'), FilterUtility::CONDITION_AND, true);
        $ds->endRestrictionGroup();
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.status, COUNT(u.id) FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'GROUP BY u.status '
            . 'HAVING COUNT(u.id) = 1',
            $qb->getDQL()
        );
    }

    public function testSeveralRestrictionsInGroup()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.id'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->where('u.id = 0');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('u.name', '1'), FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('u.name', '2'), FilterUtility::CONDITION_OR);
        $ds->addRestriction($qb->expr()->eq('u.name', '3'), FilterUtility::CONDITION_AND);
        $ds->endRestrictionGroup();
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.id FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'WHERE u.id = 0 AND '
            . '((u.name = 1 OR u.name = 2) AND u.name = 3)',
            $qb->getDQL()
        );
    }

    public function testSeveralComputedRestrictionsInGroup()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.status, COUNT(u.id)'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->groupBy('u.status');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '1'), FilterUtility::CONDITION_AND, true);
        $ds->addRestriction($qb->expr()->eq('MIN(u.id)', '2'), FilterUtility::CONDITION_OR, true);
        $ds->addRestriction($qb->expr()->eq('MAX(u.id)', '3'), FilterUtility::CONDITION_AND, true);
        $ds->endRestrictionGroup();
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.status, COUNT(u.id) FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'GROUP BY u.status '
            . 'HAVING (COUNT(u.id) = 1 OR MIN(u.id) = 2) AND MAX(u.id) = 3',
            $qb->getDQL()
        );
    }

    public function testNestedGroupsWithOneRestrictionInNestedGroup()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.id'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->where('u.id = 0');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        //  src: (1 OR (2))
        // dest: (1 OR 2)
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('u.name', '1'), FilterUtility::CONDITION_AND);
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_OR);
        $ds->addRestriction($qb->expr()->eq('u.name', '2'), FilterUtility::CONDITION_AND);
        $ds->endRestrictionGroup();
        $ds->endRestrictionGroup();
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.id FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'WHERE u.id = 0 AND '
            . '(u.name = 1 OR u.name = 2)',
            $qb->getDQL()
        );
    }

    public function testNestedGroupsWithOneComputedRestrictionInNestedGroup()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.status, COUNT(u.id)'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->groupBy('u.status')
            ->andHaving('MAX(u.id) > 0');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        //  src: (1 OR (2))
        // dest: (1 OR 2)
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '1'), FilterUtility::CONDITION_AND, true);
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_OR);
        $ds->addRestriction($qb->expr()->eq('MIN(u.id)', '2'), FilterUtility::CONDITION_AND, true);
        $ds->endRestrictionGroup();
        $ds->endRestrictionGroup();
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.status, COUNT(u.id) FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'GROUP BY u.status '
            . 'HAVING MAX(u.id) > 0 AND (COUNT(u.id) = 1 OR MIN(u.id) = 2)',
            $qb->getDQL()
        );
    }

    public function testNestedGroupsWithSameCondition()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.id'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->where('u.id = 0');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        //  src: (1 OR (2 OR 3))
        // dest: (1 OR (2 OR 3))
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('u.name', '1'), FilterUtility::CONDITION_AND);
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_OR);
        $ds->addRestriction($qb->expr()->eq('u.name', '2'), FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('u.name', '3'), FilterUtility::CONDITION_OR);
        $ds->endRestrictionGroup();
        $ds->endRestrictionGroup();
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.id FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'WHERE u.id = 0 AND '
            . '(u.name = 1 OR (u.name = 2 OR u.name = 3))',
            $qb->getDQL()
        );
    }

    public function testNestedComputedGroupsWithSameCondition()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.status, COUNT(u.id)'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->groupBy('u.status');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        //  src: (1 OR (2 OR 3))
        // dest: (1 OR (2 OR 3))
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '1'), FilterUtility::CONDITION_AND, true);
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_OR);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '2'), FilterUtility::CONDITION_AND, true);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '3'), FilterUtility::CONDITION_OR, true);
        $ds->endRestrictionGroup();
        $ds->endRestrictionGroup();
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.status, COUNT(u.id) FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'GROUP BY u.status '
            . 'HAVING COUNT(u.id) = 1 OR (COUNT(u.id) = 2 OR COUNT(u.id) = 3)',
            $qb->getDQL()
        );
    }

    public function testNestedGroupsWithDifferentConditions()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.id'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->where('u.id = 0');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        //  src: (1 OR (2 AND 3))
        // dest: (1 OR (2 AND 3))
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('u.name', '1'), FilterUtility::CONDITION_AND);
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_OR);
        $ds->addRestriction($qb->expr()->eq('u.name', '2'), FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('u.name', '3'), FilterUtility::CONDITION_AND);
        $ds->endRestrictionGroup();
        $ds->endRestrictionGroup();
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.id FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'WHERE u.id = 0 AND '
            . '(u.name = 1 OR (u.name = 2 AND u.name = 3))',
            $qb->getDQL()
        );
    }

    public function testNestedComputedGroupsWithDifferentConditions()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.status, COUNT(u.id)'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->groupBy('u.status');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        //  src: (1 OR (2 AND 3))
        // dest: (1 OR (2 AND 3))
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '1'), FilterUtility::CONDITION_AND, true);
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_OR);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '2'), FilterUtility::CONDITION_AND, true);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '3'), FilterUtility::CONDITION_AND, true);
        $ds->endRestrictionGroup();
        $ds->endRestrictionGroup();
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.status, COUNT(u.id) FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'GROUP BY u.status '
            . 'HAVING COUNT(u.id) = 1 OR (COUNT(u.id) = 2 AND COUNT(u.id) = 3)',
            $qb->getDQL()
        );
    }

    public function testComplexExpr()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.id'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->where('u.id = 0');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        //  src: (1 AND ((2 AND (3 OR 4)) OR (5) OR (6 AND 7)) AND 8)
        // dest: (1 AND ((2 AND (3 OR 4)) OR 5 OR (6 AND 7)) AND 8)
        $ds->addRestriction($qb->expr()->eq('u.name', '1'), FilterUtility::CONDITION_AND);
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('u.name', '2'), FilterUtility::CONDITION_AND);
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('u.name', '3'), FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('u.name', '4'), FilterUtility::CONDITION_OR);
        $ds->endRestrictionGroup();
        $ds->endRestrictionGroup();
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_OR);
        $ds->addRestriction($qb->expr()->eq('u.name', '5'), FilterUtility::CONDITION_AND);
        $ds->endRestrictionGroup();
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_OR);
        $ds->addRestriction($qb->expr()->eq('u.name', '6'), FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('u.name', '7'), FilterUtility::CONDITION_AND);
        $ds->endRestrictionGroup();
        $ds->endRestrictionGroup();
        $ds->addRestriction($qb->expr()->eq('u.name', '8'), FilterUtility::CONDITION_AND);
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.id FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'WHERE u.id = 0 AND '
            . '(u.name = 1 AND '
            . '((u.name = 2 AND (u.name = 3 OR u.name = 4)) OR u.name = 5 OR (u.name = 6 AND u.name = 7)) AND '
            . 'u.name = 8)',
            $qb->getDQL()
        );
    }

    public function testComplexComputedExpr()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.status, COUNT(u.id)'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->groupBy('u.status')
            ->having('COUNT(u.id) = 0');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        //  src: (1 AND ((2 AND (3 OR 4)) OR (5) OR (6 AND 7)) AND 8)
        // dest: (1 AND ((2 AND (3 OR 4)) OR 5 OR (6 AND 7)) AND 8)
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '1'), FilterUtility::CONDITION_AND, true);
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '2'), FilterUtility::CONDITION_AND, true);
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '3'), FilterUtility::CONDITION_AND, true);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '4'), FilterUtility::CONDITION_OR, true);
        $ds->endRestrictionGroup();
        $ds->endRestrictionGroup();
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_OR);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '5'), FilterUtility::CONDITION_AND, true);
        $ds->endRestrictionGroup();
        $ds->beginRestrictionGroup(FilterUtility::CONDITION_OR);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '6'), FilterUtility::CONDITION_AND, true);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '7'), FilterUtility::CONDITION_AND, true);
        $ds->endRestrictionGroup();
        $ds->endRestrictionGroup();
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '8'), FilterUtility::CONDITION_AND, true);
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.status, COUNT(u.id) FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'GROUP BY u.status '
            . 'HAVING COUNT(u.id) = 0 AND '
            . '(COUNT(u.id) = 1 AND '
            . '((COUNT(u.id) = 2 AND (COUNT(u.id) = 3 OR COUNT(u.id) = 4)) '
            . 'OR COUNT(u.id) = 5 OR (COUNT(u.id) = 6 AND COUNT(u.id) = 7)) AND '
            . 'COUNT(u.id) = 8)',
            $qb->getDQL()
        );
    }

    public function testComputedWithUnComputedRestrictionsTogether()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.status, COUNT(u.id)'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->groupBy('u.status');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        $ds->addRestriction($qb->expr()->eq('u.id', '1'), FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('u.id', '2'), FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '3'), FilterUtility::CONDITION_AND, true);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '4'), FilterUtility::CONDITION_OR, true);
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.status, COUNT(u.id) FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'WHERE u.id = 1 AND u.id = 2 '
            . 'GROUP BY u.status '
            . 'HAVING COUNT(u.id) = 3 OR COUNT(u.id) = 4',
            $qb->getDQL()
        );
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Computed conditions cannot be mixed with uncomputed.
     */
    public function testComputedWithUnComputedRestrictionsTogetherShouldReturnExceptionWhenRestrictionsAreMixed()
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['u.status, COUNT(u.id)'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'u')
            ->groupBy('u.status');
        $ds = new GroupingOrmFilterDatasourceAdapter($qb);

        $ds->addRestriction($qb->expr()->eq('u.id', '1'), FilterUtility::CONDITION_AND);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '2'), FilterUtility::CONDITION_AND, true);
        $ds->addRestriction($qb->expr()->eq('COUNT(u.id)', '3'), FilterUtility::CONDITION_OR);
        $ds->applyRestrictions();

        $this->assertEquals(
            'SELECT u.status, COUNT(u.id) FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser u '
            . 'WHERE u.id = 1 AND u.id = 2 '
            . 'GROUP BY u.status '
            . 'HAVING COUNT(u.id) = 3 OR COUNT(u.id) = 4',
            $qb->getDQL()
        );
    }
}
