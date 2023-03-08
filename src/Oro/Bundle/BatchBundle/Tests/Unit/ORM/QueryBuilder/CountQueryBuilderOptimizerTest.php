<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\QueryBuilder;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\Event\CountQueryOptimizationEvent;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer;
use Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity\BusinessUnit;
use Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity\Comment;
use Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity\Email;
use Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity\EmailNotification;
use Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity\EmailOrigin;
use Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity\Note;
use Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity\Status;
use Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity\Tagging;
use Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity\UserApi;
use Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity\UserEmail;
use Oro\Bundle\EntityBundle\Helper\RelationHelper;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use Oro\ORM\Query\AST\Functions;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CountQueryBuilderOptimizerTest extends OrmTestCase
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var RelationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $relationHelper;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
        $this->em->getConfiguration()->addCustomDatetimeFunction('date', Functions\SimpleFunction::class);
        $this->em->getConfiguration()->addCustomDatetimeFunction('convert_tz', Functions\DateTime\ConvertTz::class);

        $this->relationHelper = $this->createMock(RelationHelper::class);
        $this->relationHelper->expects($this->any())
            ->method('hasVirtualRelations')
            ->willReturn(true);

        $this->relationHelper->expects($this->any())
            ->method('getMetadataTypeForVirtualJoin')
            ->willReturnCallback(function ($entityClass, $targetEntityClass) {
                if ($targetEntityClass === Tagging::class) {
                    return ClassMetadata::ONE_TO_MANY;
                }

                return 0;
            });
    }

    /**
     * @dataProvider getCountQueryBuilderDataProvider
     */
    public function testGetCountQueryBuilder(callable $queryBuilder, string $expectedDql, string $platformClass = null)
    {
        if (null !== $platformClass) {
            $this->em->getConnection()->setDatabasePlatform(new $platformClass());
        }

        $optimizer = new CountQueryBuilderOptimizer();
        $optimizer->setRelationHelper($this->relationHelper);
        $countQb = $optimizer->getCountQueryBuilder($queryBuilder($this->em));

        $this->assertInstanceOf(QueryBuilder::class, $countQb);
        // Check for expected DQL
        $this->assertEquals($expectedDql, $countQb->getQuery()->getDQL());
        // Check that Optimized DQL can be converted to SQL
        $this->assertNotEmpty($countQb->getQuery()->getSQL());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getCountQueryBuilderDataProvider(): array
    {
        return [
            'simple' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id', 'u.username']);
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u'
            ],
            'simple with distinct id' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['DISTINCT u.id', 'u.username']);
                },
                'expectedDQL' => 'SELECT DISTINCT u.id FROM ' . User::class . ' u'
            ],
            'simple with distinct non id' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id', 'DISTINCT u.username']);
                },
                'expectedDQL' => 'SELECT DISTINCT u.username, u.id FROM ' . User::class . ' u'
            ],
            'group_test' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id', 'u.username as uName'])
                        ->groupBy('uName');
                },
                'expectedDQL' => 'SELECT u.username as _groupByPart0 FROM ' . User::class . ' u GROUP BY _groupByPart0'
            ],
            'function_having_test' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id', 'SUBSTRING(u.username, 1, 3) as uName'])
                        ->groupBy('u.id')
                        ->having("SUBSTRING(u.username, 1, 3) LIKE 'A%'");
                },
                'expectedDQL' => 'SELECT u.id as _groupByPart0 ' .
                    'FROM ' . User::class . ' u ' .
                    'GROUP BY _groupByPart0 ' .
                    "HAVING SUBSTRING(u.username, 1, 3) LIKE 'A%'"
            ],
            'function_group_test' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id', 'SUBSTRING(u.username, 1, 3) as uName'])
                        ->groupBy('uName');
                },
                'expectedDQL' => 'SELECT SUBSTRING(u.username, 1, 3) as _groupByPart0 ' .
                    'FROM ' . User::class . ' u ' .
                    'GROUP BY _groupByPart0'
            ],
            'complex_group_by' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id', 'SUBSTRING(u.username, 1, 3) as uName'])
                        ->groupBy('u.id, uName');
                },
                'expectedDQL' => 'SELECT u.id as _groupByPart0, SUBSTRING(u.username, 1, 3) as _groupByPart1 ' .
                    'FROM ' . User::class . ' u ' .
                    'GROUP BY _groupByPart0, _groupByPart1'
            ],
            'one_table' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id', 'u.username'])
                        ->where('u.id=10')
                        ->andWhere('LOWER(u.username) LIKE :testParameter')
                        ->groupBy('u.id')
                        ->having('u.username = :testParameter');
                },
                'expectedDQL' => 'SELECT u.id as _groupByPart0 FROM ' . User::class . ' u '
                    . 'WHERE u.id=10 AND LOWER(u.username) LIKE :testParameter '
                    . 'GROUP BY _groupByPart0 '
                    . 'HAVING u.username = :testParameter'
            ],
            'unused_left_join' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->leftJoin(UserApi::class, 'api')
                        ->select(['u.id', 'u.username', 'api.apiKey']);
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u',
            ],
            'unused_left_join_without_conditions' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->leftJoin('u.owner', 'o')
                        ->select('u.id, o.name');
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u',
            ],
            'unused_left_join_with_condition' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->leftJoin('u.owner', 'o', Join::WITH, 'o.id = 123')
                        ->select('u.id, o.name');
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u',
            ],
            'unused_left_join_with_condition_in_several_joins' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->leftJoin('u.businessUnits', 'bu', Join::WITH, 'bu.id = 456')
                        ->leftJoin('bu.users', 'o', Join::WITH, 'o.id = 123')
                        ->select('u.id, o.username');
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u '
                    . 'LEFT JOIN u.businessUnits bu WITH bu.id = 456 '
                    . 'LEFT JOIN bu.users o WITH o.id = 123',
            ],
            'used_left_join' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->leftJoin(UserApi::class, 'api')
                        ->select(['u.id', 'u.username', 'api.apiKey as aKey'])
                        ->where('aKey = :test')
                        ->setParameter('test', 'test_api_key');
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u '
                    . 'LEFT JOIN ' . UserApi::class . ' api '
                    . 'WHERE api.apiKey = :test',
            ],
            'with_inner_join' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->innerJoin('u.businessUnits', 'bu')
                        ->leftJoin('bu.organization', 'o')
                        ->select(['u.id', 'u.username', 'api.apiKey as aKey']);
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u '
                    . 'INNER JOIN u.businessUnits bu'
            ],
            'with_inner_join_with_condition' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->innerJoin(BusinessUnit::class, 'bu', Join::WITH, 'u.owner = bu.id')
                        ->leftJoin(UserApi::class, 'api')
                        ->select(['u.id', 'u.username', 'api.apiKey as aKey']);
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u '
                    . 'INNER JOIN ' . BusinessUnit::class . ' bu WITH u.owner = bu.id'
            ],
            'with_inner_join_depends_on_left_join' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->innerJoin(BusinessUnit::class, 'bu', Join::WITH, 'owner.id = bu.id')
                        ->leftJoin('u.owner', 'owner')
                        ->select(['u.id']);
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u '
                    . 'INNER JOIN ' . BusinessUnit::class . ' bu WITH owner.id = bu.id '
                    . 'LEFT JOIN u.owner owner'
            ],
            'with_mediate_inner_join' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Group::class, 'g')
                        ->leftJoin('g.owner', 'bu')
                        ->innerJoin('bu.organization', 'o')
                        ->leftJoin('o.users', 'u')
                        ->select(['g.id']);
                },
                'expectedDQL' => 'SELECT g.id FROM ' . Group::class . ' g '
                    . 'LEFT JOIN g.owner bu '
                    . 'INNER JOIN bu.organization o '
                    . 'LEFT JOIN o.users u'
            ],
            'inner_with_2_left_group' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->innerJoin('u.owner', 'bu')
                        ->leftJoin('u.groups', 'g')
                        ->leftJoin('u.roles', 'r')
                        ->leftJoin('g.roles', 'gr')
                        ->select(['u.id', 'u.username', 'api.apiKey as aKey'])
                        ->groupBy('gr.id');
                },
                'expectedDQL' => 'SELECT gr.id as _groupByPart0 FROM ' . User::class . ' u '
                    . 'INNER JOIN u.owner bu '
                    . 'LEFT JOIN u.groups g '
                    . 'LEFT JOIN g.roles gr '
                    . 'GROUP BY _groupByPart0'
            ],
            'inner_with_2_left_group_and_having' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->innerJoin('u.owner', 'bu')
                        ->leftJoin('u.groups', 'g')
                        ->leftJoin('u.roles', 'r')
                        ->leftJoin('g.roles', 'gr')
                        ->select(['u.id', 'u.username', 'api.apiKey as aKey'])
                        ->groupBy('gr.id')
                        ->having('u.username LIKE :test');
                },
                'expectedDQL' => 'SELECT gr.id as _groupByPart0 FROM ' . User::class . ' u '
                    . 'INNER JOIN u.owner bu '
                    . 'LEFT JOIN u.groups g '
                    . 'LEFT JOIN g.roles gr '
                    . 'GROUP BY _groupByPart0 '
                    . 'HAVING u.username LIKE :test'
            ],
            'inner_with_3_left_having' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->innerJoin('u.owner', 'bu')
                        ->leftJoin('u.groups', 'g')
                        ->leftJoin('u.roles', 'r')
                        ->leftJoin('g.roles', 'gr')
                        ->select(['u.id', 'u.username', 'api.apiKey as aKey'])
                        ->groupBy('u.id')
                        ->having('gr.label LIKE :test');
                },
                'expectedDQL' => 'SELECT u.id as _groupByPart0 FROM ' . User::class . ' u '
                    . 'INNER JOIN u.owner bu '
                    . 'LEFT JOIN u.groups g '
                    . 'LEFT JOIN g.roles gr '
                    . 'GROUP BY _groupByPart0 '
                    . 'HAVING gr.label LIKE :test'
            ],
            'third_join_in_on' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->innerJoin('u.owner', 'bu')
                        ->leftJoin('u.groups', 'g')
                        ->leftJoin('u.roles', 'r')
                        ->leftJoin('g.roles', 'gr', Join::WITH, 'aKey = :test')
                        ->leftJoin('u.apiKeys', 'api')
                        ->select(['u.id', 'u.username', 'api.apiKey as aKey'])
                        ->where('gr.id > 10');
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u '
                    . 'INNER JOIN u.owner bu '
                    . 'LEFT JOIN u.groups g '
                    . 'LEFT JOIN u.roles r '
                    . 'LEFT JOIN g.roles gr WITH api.apiKey = :test '
                    . 'LEFT JOIN u.apiKeys api '
                    . 'WHERE gr.id > 10'
            ],
            'having_equal' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id', 'u.username as login', 'api.apiKey as aKey'])
                        ->groupBy('u.id')
                        ->having('login = :test');
                },
                'expectedDQL' => 'SELECT u.id as _groupByPart0 FROM ' . User::class . ' u '
                    . 'GROUP BY _groupByPart0 '
                    . 'HAVING u.username = :test'
            ],
            'having_in' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id', 'u.username as login', 'api.apiKey as aKey'])
                        ->groupBy('u.id')
                        ->having('login IN (?0)');
                },
                'expectedDQL' => 'SELECT u.id as _groupByPart0 FROM ' . User::class . ' u '
                    . 'GROUP BY _groupByPart0 '
                    . 'HAVING u.username IN (?0)'
            ],
            'having_like' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id', 'u.username as login', 'api.apiKey as aKey'])
                        ->groupBy('u.id')
                        ->having('login LIKE :test');
                },
                'expectedDQL' => 'SELECT u.id as _groupByPart0 FROM ' . User::class . ' u '
                    . 'GROUP BY _groupByPart0 '
                    . 'HAVING u.username LIKE :test'
            ],
            'having_is_null' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id', 'u.username as login', 'api.apiKey as aKey'])
                        ->groupBy('u.id')
                        ->having('login IS NULL');
                },
                'expectedDQL' => 'SELECT u.id as _groupByPart0 FROM ' . User::class . ' u '
                    . 'GROUP BY _groupByPart0 '
                    . 'HAVING u.username IS NULL'
            ],
            'having_is_not_null' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id', 'u.username as login', 'api.apiKey as aKey'])
                        ->groupBy('u.id')
                        ->having('login IS NOT NULL');
                },
                'expectedDQL' => 'SELECT u.id as _groupByPart0 FROM ' . User::class . ' u '
                    . 'GROUP BY _groupByPart0 '
                    . 'HAVING u.username IS NOT NULL'
            ],
            'having_instead_where' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id', 'u.username as login', 'api.apiKey as aKey'])
                        ->having('login LIKE :test');
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u WHERE u.username LIKE :test'
            ],
            'having_by_expr_with_function_mysql' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['DATE(u.createdAt) as createdDate', 'COUNT(u.id) as count'])
                        ->groupBy('createdDate')
                        ->having('createdDate > :date');
                },
                'expectedDQL' => 'SELECT DATE(u.createdAt) as _groupByPart0 '
                    . 'FROM ' . User::class . ' u '
                    . 'GROUP BY _groupByPart0 '
                    . 'HAVING _groupByPart0 > :date',
                'platformClass' => MySqlPlatform::class
            ],
            'having_by_expr_with_function_postgresql' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['DATE(u.createdAt) as createdDate', 'COUNT(u.id) as count'])
                        ->groupBy('createdDate')
                        ->having('createdDate > :date');
                },
                'expectedDQL' => 'SELECT DATE(u.createdAt) as _groupByPart0 '
                    . 'FROM ' . User::class . ' u '
                    . 'GROUP BY _groupByPart0 '
                    . 'HAVING DATE(u.createdAt) > :date',
                'platformClass' => PostgreSqlPlatform::class
            ],
            'having_by_expr_with_nested_functions_mysql' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select([
                            'DATE(CONVERT_TZ(u.createdAt, \'+00:00\', \'+03:00\')) as createdDate',
                            'COUNT(u.id) as count'
                        ])
                        ->groupBy('createdDate')
                        ->having('createdDate > :date');
                },
                'expectedDQL' => 'SELECT DATE(CONVERT_TZ(u.createdAt, \'+00:00\', \'+03:00\')) as _groupByPart0 '
                    . 'FROM ' . User::class . ' u '
                    . 'GROUP BY _groupByPart0 '
                    . 'HAVING _groupByPart0 > :date',
                'platformClass' => MySqlPlatform::class
            ],
            'having_by_expr_with_nested_functions_postgresql' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select([
                            'DATE(CONVERT_TZ(u.createdAt, \'+00:00\', \'+03:00\')) as createdDate',
                            'COUNT(u.id) as count'
                        ])
                        ->groupBy('createdDate')
                        ->having('createdDate > :date');
                },
                'expectedDQL' => 'SELECT DATE(CONVERT_TZ(u.createdAt, \'+00:00\', \'+03:00\')) as _groupByPart0 '
                    . 'FROM ' . User::class . ' u '
                    . 'GROUP BY _groupByPart0 '
                    . 'HAVING DATE(CONVERT_TZ(u.createdAt, \'+00:00\', \'+03:00\')) > :date',
                'platformClass' => PostgreSqlPlatform::class
            ],
            'join_on_table_that_has_with_join_condition' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id'])
                        ->leftJoin(UserEmail::class, 'e', Join::WITH, 'e.user = u')
                        ->leftJoin('e.user', 'eu')
                        ->leftJoin('eu.owner', 'euo')
                        ->where('euo.name = :name');
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u '
                    . 'LEFT JOIN ' . UserEmail::class . ' e WITH e.user = u '
                    . 'LEFT JOIN e.user eu '
                    . 'LEFT JOIN eu.owner euo WHERE euo.name = :name'
            ],
            'join_on_table_that_has_with_join_and_join_on_alias_condition' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id'])
                        ->leftJoin(UserEmail::class, 'e', Join::WITH, 'e.user = u')
                        ->leftJoin('e.user', 'eu')
                        ->leftJoin(Status::class, 's', Join::WITH, 's.user = eu')
                        ->where('s.status = :statusName');
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u '
                    . 'LEFT JOIN ' . UserEmail::class . ' e WITH e.user = u '
                    . 'LEFT JOIN ' . Status::class . ' s WITH s.user = e.user '
                    . 'WHERE s.status = :statusName'
            ],
            'join_on_table_that_has_with_join_and_join_on_alias_condition_and_group_by' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id'])
                        ->leftJoin(UserEmail::class, 'e', Join::WITH, 'e.user = u')
                        ->leftJoin('e.user', 'eu')
                        ->leftJoin(Status::class, 's', Join::WITH, 's.user = eu')
                        ->groupBy('eu.username')
                        ->where('s.status = :statusName');
                },
                'expectedDQL' => 'SELECT eu.username as _groupByPart0 FROM ' . User::class . ' u '
                    . 'LEFT JOIN ' . UserEmail::class . ' e WITH e.user = u '
                    . 'LEFT JOIN e.user eu '
                    . 'LEFT JOIN ' . Status::class . ' s WITH s.user = e.user '
                    . 'WHERE s.status = :statusName '
                    . 'GROUP BY _groupByPart0'
            ],
            'join_one_to_many_table_and_many_to_one_table' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id'])
                        ->leftJoin(Email::class, 'e', Join::WITH, 'u MEMBER OF e.users')
                        ->leftJoin(Comment::class, 'c', Join::WITH, 'c.email = e')
                        ->leftJoin(Note::class, 'n', Join::WITH, 'c.note = n')
                        ->leftJoin(
                            Tagging::class,
                            't',
                            Join::WITH,
                            "t.recordId = u.id AND t.entityName = '" . User::class . "'"
                        )
                        ->leftJoin('t.tag', 'tag');
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u '
                    . 'LEFT JOIN ' . Email::class . ' e WITH u MEMBER OF e.users '
                    . 'LEFT JOIN ' . Comment::class . ' c WITH c.email = e '
                    . 'LEFT JOIN ' . Tagging::class
                    . " t WITH t.recordId = u.id AND t.entityName = '" . User::class . "'"
            ],
            'join_one_to_many_table' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id'])
                        ->leftJoin(Email::class, 'e', Join::WITH, 'u MEMBER OF e.users');
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u '
                    . 'LEFT JOIN ' . Email::class . ' e WITH u MEMBER OF e.users'
            ],
            'unidirectional_join_one_to_many_table' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id'])
                        ->leftJoin(EmailOrigin::class, 'eo', Join::WITH, 'eo MEMBER OF u.emailOrigins');
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u '
                    . 'LEFT JOIN ' . EmailOrigin::class . ' eo WITH eo MEMBER OF u.emailOrigins'
            ],
            'join_many_to_many_table' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->select(['u.id'])
                        ->leftJoin('u.businessUnits', 'b');
                },
                'expectedDQL' => 'SELECT u.id FROM ' . User::class . ' u '
                    . 'LEFT JOIN u.businessUnits b'
            ],
            'join_many_to_many_depends_on_one_to_one' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->select(['e.id'])
                        ->from(EmailNotification::class, 'e')
                        ->leftJoin('e.recipientList', 'recipientList')
                        ->leftJoin('recipientList.users', 'recipientUsersList');
                },
                'expectedDQL' => 'SELECT e.id FROM ' . EmailNotification::class . ' e '
                    . 'LEFT JOIN e.recipientList recipientList '
                    . 'LEFT JOIN recipientList.users recipientUsersList'
            ],
            'several_from' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Group::class, 'g')
                        ->from(BusinessUnit::class, 'bu')
                        ->leftJoin('bu.organization', 'o')
                        ->select(['g.id']);
                },
                'expectedDQL' => 'SELECT g.id, bu.id FROM ' . Group::class . ' g, ' . BusinessUnit::class . ' bu'
            ],
            'several_from_with_unused_crossed_dependency' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Group::class, 'g')
                        ->from(BusinessUnit::class, 'bu')
                        ->leftJoin('bu.organization', 'o')
                        ->leftJoin('g.owner', 'gbu', Join::WITH, 'gbu MEMBER OF o.businessUnits')
                        ->select(['g.id']);
                },
                'expectedDQL' => 'SELECT g.id, bu.id FROM ' . Group::class . ' g, ' . BusinessUnit::class . ' bu'
            ],
            'several_from_with_used_crossed_dependency' => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(Group::class, 'g')
                        ->from(BusinessUnit::class, 'bu')
                        ->leftJoin('bu.organization', 'o')
                        ->innerJoin('g.owner', 'gbu', Join::WITH, 'gbu MEMBER OF o.businessUnits')
                        ->select(['g.id']);
                },
                'expectedDQL' => 'SELECT g.id, bu.id FROM '
                    . Group::class . ' g INNER JOIN g.owner gbu WITH g.owner MEMBER OF o.businessUnits, '
                    . BusinessUnit::class . ' bu LEFT JOIN bu.organization o'
            ],
        ];
    }

    /**
     * @dataProvider getCountQueryBuilderDataProviderWithEventDispatcher
     */
    public function testGetCountQueryBuilderWithEventDispatcher(
        callable $queryBuilder,
        array $joinsToDelete,
        string $expectedDql
    ) {
        $optimizer = new CountQueryBuilderOptimizer();
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (CountQueryOptimizationEvent $event) use ($joinsToDelete) {
                if (count($joinsToDelete)) {
                    foreach ($joinsToDelete as $deletedJoin) {
                        $event->removeJoinFromOptimizedQuery($deletedJoin);
                    }
                }

                return $event;
            });
        $optimizer->setEventDispatcher($eventDispatcher);
        $countQb = $optimizer->getCountQueryBuilder($queryBuilder($this->em));

        $this->assertInstanceOf(QueryBuilder::class, $countQb);
        // Check for expected DQL
        $this->assertEquals($expectedDql, $countQb->getQuery()->getDQL());
        // Check that Optimized DQL can be converted to SQL
        $this->assertNotEmpty($countQb->getQuery()->getSQL());
    }

    public function getCountQueryBuilderDataProviderWithEventDispatcher(): array
    {
        return [
            'delete_one_join_table'                                     => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->leftJoin('u.businessUnits', 'bu', Join::WITH, 'bu.id = 456')
                        ->leftJoin('bu.users', 'o', Join::WITH, 'o.id = 123')
                        ->select('u.id, o.username');
                },
                ['o'],
                'expectedDQL'  => 'SELECT u.id FROM ' . User::class . ' u '
                    . 'LEFT JOIN u.businessUnits bu WITH bu.id = 456'
            ],
            'request_with_3th_join_table_without_deleting'              => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->leftJoin('u.organization', 'o', Join::WITH, 'o.id = 456')
                        ->leftJoin('o.businessUnits', 'businessUnits', Join::WITH, 'businessUnits.id = 123')
                        ->select('u.id, o.username');
                },
                [],
                'expectedDQL'  => 'SELECT u.id FROM ' . User::class . ' u '
                    . 'LEFT JOIN u.organization o WITH o.id = 456 '
                    . 'LEFT JOIN o.businessUnits businessUnits WITH businessUnits.id = 123'
            ],
            'request_with_3th_join_table_delete_main_join_table'        => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->leftJoin('u.organization', 'o', Join::WITH, 'o.id = 456')
                        ->leftJoin('o.businessUnits', 'businessUnits', Join::WITH, 'businessUnits.id = 123')
                        ->select('u.id, o.username');
                },
                ['o'],
                'expectedDQL'  => 'SELECT u.id FROM ' . User::class . ' u LEFT '
                    . 'JOIN u.organization o WITH o.id = 456 '
                    . 'LEFT JOIN o.businessUnits businessUnits WITH businessUnits.id = 123'
            ],
            'request_with_3th_join_table_delete_3th_join_table'         => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(User::class, 'u')
                        ->leftJoin('u.organization', 'o', Join::WITH, 'o.id = 456')
                        ->leftJoin('o.businessUnits', 'businessUnits', Join::WITH, 'businessUnits.id = 123')
                        ->select('u.id, o.username');
                },
                ['businessUnits'],
                'expectedDQL'  => 'SELECT u.id FROM ' . User::class . ' u'
            ],
            'request_with_2_3th_join_tables_without_deleting'           => [
                'queryBuilder' => function ($em) {
                    return $this->getQueryBuilderWith3thJoinTables($em);
                },
                [],
                'expectedDQL'  => 'SELECT u.id FROM ' . User::class . ' u '
                    . 'LEFT JOIN u.organization o WITH o.id = 456 '
                    . 'LEFT JOIN o.businessUnits businessUnits WITH businessUnits.id = 123 '
                    . 'LEFT JOIN o.users users WITH users.id = 123'
            ],
            'request_with_2_3th_join_tables_delete_3th_join_table'      => [
                'queryBuilder' => function ($em) {
                    return $this->getQueryBuilderWith3thJoinTables($em);
                },
                ['businessUnits'],
                'expectedDQL'  => 'SELECT u.id FROM ' . User::class . ' u '
                    . 'LEFT JOIN u.organization o WITH o.id = 456 '
                    . 'LEFT JOIN o.users users WITH users.id = 123'
            ],
            'request_with_2_3th_join_tables_delete_both_3th_join_table' => [
                'queryBuilder' => function ($em) {
                    return $this->getQueryBuilderWith3thJoinTables($em);
                },
                ['businessUnits', 'users'],
                'expectedDQL'  => 'SELECT u.id FROM ' . User::class . ' u'
            ],
            'request_with_3_joins_2_manyToOne_through_oneToMany'        => [
                'queryBuilder' => function ($em) {
                    return (new QueryBuilder($em))
                        ->from(EmailOrigin::class, 'eo')
                        ->leftJoin('eo.owner', 'u')
                        ->leftJoin('u.emails', 'primaryEmail', Join::WITH, 'primaryEmail.primary = true')
                        ->leftJoin('primaryEmail.status', 'emailStatus')
                        ->where('emailStatus.name IS NOT NULL')
                        ->select('u.id');
                },
                ['primaryEmail'],
                'expectedDQL'  => 'SELECT eo.id FROM ' . EmailOrigin::class . ' eo LEFT JOIN eo.owner u '
                    . 'LEFT JOIN u.emails primaryEmail WITH primaryEmail.primary = true '
                    . 'LEFT JOIN primaryEmail.status emailStatus WHERE emailStatus.name IS NOT NULL'
            ]
        ];
    }

    private function getQueryBuilderWith3thJoinTables(EntityManager $em): QueryBuilder
    {
        return (new QueryBuilder($em))
            ->from(User::class, 'u')
            ->leftJoin('u.organization', 'o', Join::WITH, 'o.id = 456')
            ->leftJoin('o.businessUnits', 'businessUnits', Join::WITH, 'businessUnits.id = 123')
            ->leftJoin('o.users', 'users', Join::WITH, 'users.id = 123')
            ->select('u.id, o.username');
    }
}
