<?php

namespace Oro\Bundle\BatchBundle\Tests\Functional\ORM\QueryBuilder;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CountQueryBuilderOptimizerTest extends WebTestCase
{
    /**
     * @var array
     */
    protected static $queryBuilders = array();

    /**
     * Initialize kernel and create query builders for data provider
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function setUpBeforeClass()
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }
        static::$kernel = static::createKernel(array("debug" => false));
        static::$kernel->boot();

        $container = static::$kernel->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        $simpleQb = new QueryBuilder($em);
        $simpleQb->from('OroUserBundle:User', 'u')
            ->select(array('u.id', 'u.username'));

        $groupQb = new QueryBuilder($em);
        $groupQb->from('OroUserBundle:User', 'u')
            ->select(array('u.id', 'u.username as uName'))
            ->groupBy('uName');

        $functionGroupQb = new QueryBuilder($em);
        $functionGroupQb->from('OroUserBundle:User', 'u')
            ->select(array('u.id', 'SUBSTRING(u.username, 1, 3) as uName'))
            ->groupBy('uName');

        $oneTableQb = new QueryBuilder($em);
        $oneTableQb->from('OroUserBundle:User', 'u')
            ->select(array('u.id', 'u.username'))
            ->where('u.id=10')
            ->andWhere('LOWER(u.username) LIKE :testParameter')
            ->groupBy('u.id')
            ->having('u.username = :testParameter');

        $unusedLeftJoinQb = new QueryBuilder($em);
        $unusedLeftJoinQb->from('OroUserBundle:User', 'u')
            ->leftJoin('OroUserBundle:UserApi', 'api')
            ->select(array('u.id', 'u.username', 'api.apiKey'));

        $usedLeftJoinQb = new QueryBuilder($em);
        $usedLeftJoinQb->from('OroUserBundle:User', 'u')
            ->leftJoin('OroUserBundle:UserApi', 'api')
            ->select(array('u.id', 'u.username', 'api.apiKey as aKey'))
            ->where('aKey = :test')
            ->setParameter('test', 'test_api_key');

        $withInnerJoinQb = new QueryBuilder($em);
        $withInnerJoinQb->from('OroUserBundle:User', 'u')
            ->innerJoin('OroOrganizationBundle:BusinessUnit', 'bu', Join::WITH, 'u.owner = bu.id')
            ->leftJoin('OroUserBundle:UserApi', 'api')
            ->select(array('u.id', 'u.username', 'api.apiKey as aKey'));

        $withInnerJoinAndTwoLeftGroupQb = new QueryBuilder($em);
        $withInnerJoinAndTwoLeftGroupQb->from('OroUserBundle:User', 'u')
            ->innerJoin('u.owner', 'bu')
            ->leftJoin('u.groups', 'g')
            ->leftJoin('u.roles', 'r')
            ->leftJoin('g.roles', 'gr')
            ->select(array('u.id', 'u.username', 'api.apiKey as aKey'))
            ->groupBy('gr.id')
            ->having('u.username LIKE :test');

        $withInnerJoinAndTwoLeftHavingQb = new QueryBuilder($em);
        $withInnerJoinAndTwoLeftHavingQb->from('OroUserBundle:User', 'u')
            ->innerJoin('u.owner', 'bu')
            ->leftJoin('u.groups', 'g')
            ->leftJoin('u.roles', 'r')
            ->leftJoin('g.roles', 'gr')
            ->select(array('u.id', 'u.username', 'api.apiKey as aKey'))
            ->groupBy('u.id')
            ->having('gr.label LIKE :test');

        $thirdLeftJoinInOnQb = new QueryBuilder($em);
        $thirdLeftJoinInOnQb->from('OroUserBundle:User', 'u')
            ->innerJoin('u.owner', 'bu')
            ->leftJoin('u.groups', 'g')
            ->leftJoin('u.roles', 'r')
            ->leftJoin('g.roles', 'gr', Join::WITH, 'aKey = :test')
            ->leftJoin('u.api', 'api')
            ->select(array('u.id', 'u.username', 'api.apiKey as aKey'))
            ->where('gr.id > 10');

        $havingEqualQb = new QueryBuilder($em);
        $havingEqualQb->from('OroUserBundle:User', 'u')
            ->select(array('u.id', 'u.username as login', 'api.apiKey as aKey'))
            ->groupBy('u.id')
            ->having('login = :test');

        $havingInQb = new QueryBuilder($em);
        $havingInQb->from('OroUserBundle:User', 'u')
            ->select(array('u.id', 'u.username as login', 'api.apiKey as aKey'))
            ->groupBy('u.id')
            ->having('login IN (?0)');

        $havingLikeQb = new QueryBuilder($em);
        $havingLikeQb->from('OroUserBundle:User', 'u')
            ->select(array('u.id', 'u.username as login', 'api.apiKey as aKey'))
            ->groupBy('u.id')
            ->having('login LIKE :test');

        $havingIsNullQb = new QueryBuilder($em);
        $havingIsNullQb->from('OroUserBundle:User', 'u')
            ->select(array('u.id', 'u.username as login', 'api.apiKey as aKey'))
            ->groupBy('u.id')
            ->having('login IS NULL');

        $havingIsNotNullQb = new QueryBuilder($em);
        $havingIsNotNullQb->from('OroUserBundle:User', 'u')
            ->select(array('u.id', 'u.username as login', 'api.apiKey as aKey'))
            ->groupBy('u.id')
            ->having('login IS NOT NULL');

        $havingInsteadWhereQb = new QueryBuilder($em);
        $havingInsteadWhereQb->from('OroUserBundle:User', 'u')
            ->select(array('u.id', 'u.username as login', 'api.apiKey as aKey'))
            ->having('login LIKE :test');

        self::$queryBuilders = array(
            'simple' => $simpleQb,
            'group_test' => $groupQb,
            'function_group_test' => $functionGroupQb,
            'one_table' => $oneTableQb,
            'unused_left_join' => $unusedLeftJoinQb,
            'used_left_join' => $usedLeftJoinQb,
            'with_inner_join' => $withInnerJoinQb,
            'inner_with_2_left_group' => $withInnerJoinAndTwoLeftGroupQb,
            'inner_with_2_left_having' => $withInnerJoinAndTwoLeftHavingQb,
            'third_join_in_on' => $thirdLeftJoinInOnQb,
            'having_equal' => $havingEqualQb,
            'having_in' => $havingInQb,
            'having_like' => $havingLikeQb,
            'having_is_null' => $havingIsNullQb,
            'having_is_not_null' => $havingIsNotNullQb,
            'having_instead_where' => $havingInsteadWhereQb
        );
    }

    /**
     * @dataProvider queryBuilderDataProvider
     * @param string $qbKey
     * @param string $expectedDql
     */
    public function testGetCountQueryBuilder($qbKey, $expectedDql)
    {
        $optimizer = new CountQueryBuilderOptimizer();
        $countQb = $optimizer->getCountQueryBuilder(self::$queryBuilders[$qbKey]);

        $this->assertInstanceOf('Doctrine\ORM\QueryBuilder', $countQb);
        // Check for expected DQL
        $this->assertEquals($expectedDql, $countQb->getQuery()->getDQL());
        // Check that Optimized DQL can be converted to SQL
        $this->assertNotEmpty($countQb->getQuery()->getSQL());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function queryBuilderDataProvider()
    {
        return array(
            'simple' => array(
                'simple',
                'SELECT u.id FROM OroUserBundle:User u'
            ),
            'group_test' => array(
                'group_test',
                'SELECT u.id, u.username as uName FROM OroUserBundle:User u GROUP BY uName'
            ),
            'function_group_test' => array(
                'function_group_test',
                'SELECT u.id, SUBSTRING(u.username, 1, 3) as uName FROM OroUserBundle:User u GROUP BY uName'
            ),
            'one_table' => array(
                'one_table',
                'SELECT u.id, u.username as _havingField0 FROM OroUserBundle:User u '
                . 'WHERE u.id=10 AND LOWER(u.username) LIKE :testParameter '
                . 'GROUP BY u.id '
                . 'HAVING u.username = :testParameter'
            ),
            'unused_left_join table' => array(
                'unused_left_join',
                'SELECT u.id FROM OroUserBundle:User u'
            ),
            'used_left_join table' => array(
                'used_left_join',
                'SELECT DISTINCT u.id FROM OroUserBundle:User u '
                . 'LEFT JOIN OroUserBundle:UserApi api '
                . 'WHERE api.apiKey = :test'
            ),
            'with_inner_join' => array(
                'with_inner_join',
                'SELECT DISTINCT u.id FROM OroUserBundle:User u '
                    . 'INNER JOIN OroOrganizationBundle:BusinessUnit bu WITH u.owner = bu.id'
            ),
            'inner_with_2_left_group' => array(
                'inner_with_2_left_group',
                'SELECT DISTINCT u.id, u.username as _havingField0 FROM OroUserBundle:User u '
                    . 'INNER JOIN u.owner bu '
                    . 'LEFT JOIN u.groups g '
                    . 'LEFT JOIN g.roles gr '
                    . 'GROUP BY gr.id '
                    . 'HAVING u.username LIKE :test'
            ),
            'inner_with_2_left_having' => array(
                'inner_with_2_left_having',
                'SELECT DISTINCT u.id, gr.label as _havingField0 FROM OroUserBundle:User u '
                    . 'INNER JOIN u.owner bu '
                    . 'LEFT JOIN u.groups g '
                    . 'LEFT JOIN g.roles gr '
                    . 'GROUP BY u.id '
                    . 'HAVING gr.label LIKE :test'
            ),
            'third_join_in_on' => array(
                'third_join_in_on',
                'SELECT DISTINCT u.id FROM OroUserBundle:User u '
                    . 'INNER JOIN u.owner bu '
                    . 'LEFT JOIN u.groups g '
                    . 'LEFT JOIN g.roles gr WITH api.apiKey = :test '
                    . 'LEFT JOIN u.api api '
                    . 'WHERE gr.id > 10'
            ),
            'having_equal' => array(
                'having_equal',
                'SELECT u.id, u.username as login FROM OroUserBundle:User u '
                    . 'GROUP BY u.id '
                    . 'HAVING login = :test'
            ),
            'having_in' => array(
                'having_in',
                'SELECT u.id, u.username as login FROM OroUserBundle:User u '
                . 'GROUP BY u.id '
                . 'HAVING login IN (?0)'
            ),
            'having_like' => array(
                'having_like',
                'SELECT u.id, u.username as login FROM OroUserBundle:User u '
                . 'GROUP BY u.id '
                . 'HAVING u.username LIKE :test'
            ),
            'having_is_null' => array(
                'having_is_null',
                'SELECT u.id, u.username as login FROM OroUserBundle:User u '
                . 'GROUP BY u.id '
                . 'HAVING u.username IS NULL'
            ),
            'having_is_not_null' => array(
                'having_is_not_null',
                'SELECT u.id, u.username as login FROM OroUserBundle:User u '
                . 'GROUP BY u.id '
                . 'HAVING u.username IS NOT NULL'
            ),
            'having_instead_where' => array(
                'having_instead_where',
                'SELECT u.id FROM OroUserBundle:User u WHERE u.username LIKE :test'
            )
        );
    }
}
