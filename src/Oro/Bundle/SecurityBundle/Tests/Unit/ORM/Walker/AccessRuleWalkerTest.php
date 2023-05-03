<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\ORM\Walker;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleExecutor;
use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleOptionMatcherInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\AccessDenied;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Exists;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\NullComparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Subquery;
use Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalker;
use Oro\Bundle\SecurityBundle\ORM\Walker\AccessRuleWalkerContext;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\AccessRule\DynamicAccessRule;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsArticle;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsOrganization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AccessRuleWalkerTest extends OrmTestCase
{
    private EntityManagerInterface $em;
    private DynamicAccessRule $rule;
    private AccessRuleExecutor $accessRuleExecutor;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $this->rule = new DynamicAccessRule();
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::any())
            ->method('get')
            ->with('rule')
            ->willReturn($this->rule);
        $matcher = $this->createMock(AccessRuleOptionMatcherInterface::class);
        $matcher->expects(self::any())
            ->method('matches')
            ->willReturn(true);
        $this->accessRuleExecutor = new AccessRuleExecutor(
            [['rule', []]],
            $container,
            $matcher
        );
    }

    private function prepareQuery(Query $dqlQuery, array $contextOptions = []): void
    {
        $context = new AccessRuleWalkerContext($this->accessRuleExecutor, 'VIEW', CmsUser::class, 1);
        foreach ($contextOptions as $optionName => $optionValue) {
            $context->setOption($optionName, $optionValue);
        }
        $dqlQuery->setHint(AccessRuleWalker::CONTEXT, $context);
        $dqlQuery->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [AccessRuleWalker::class]);
    }

    private function assertResultQueryEquals(string $expectedQuery, Query $dqlQuery, array $contextOptions = []): void
    {
        $this->prepareQuery($dqlQuery, $contextOptions);
        $this->assertEquals($expectedQuery, $dqlQuery->getSQL());
    }

    public function testWalkerWithEmptyRules()
    {
        $this->rule->setRule(function () {
        });

        $query = $this->em->getRepository(CmsAddress::class)->createQueryBuilder('address')
            ->select('address.id')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0 FROM cms_addresses c0_';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerWithSimpleComparisonExpression()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->andExpression(new Comparison(new Path('user'), Comparison::IN, [1, 2, 3, 4, 5]));
        });

        $query = $this->em->getRepository(CmsAddress::class)->createQueryBuilder('address')
            ->select('address.id')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_addresses c0_'
            . ' WHERE c0_.user_id IN (1, 2, 3, 4, 5)';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerQueryWithWhereAndWithSimpleComparisonExpression()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->orExpression(new Comparison(new Path('user'), Comparison::IN, [1, 2, 3, 4, 5]));
        });

        $query = $this->em->getRepository(CmsAddress::class)->createQueryBuilder('address')
            ->select('address.id')
            ->where('address.country = :country')
            ->setParameter('country', 'US')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_addresses c0_'
            . ' WHERE c0_.country = ? AND c0_.user_id IN (1, 2, 3, 4, 5)';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerQueryWithWhereAndWithCompositeExpression()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->andExpression(new Comparison(new Path('user'), Comparison::IN, [1, 2, 3, 4, 5]));
            $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
            $criteria->orExpression(new Comparison(new Path('user'), Comparison::EQ, 20));
        });

        $query = $this->em->getRepository(CmsAddress::class)->createQueryBuilder('address')
            ->select('address.id')
            ->where('address.country = :country')
            ->setParameter('country', 'US')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_addresses c0_'
            . ' WHERE c0_.country = ?'
            . ' AND ((c0_.user_id IN (1, 2, 3, 4, 5) AND c0_.organization_id = 1) OR c0_.user_id = 20)';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerQueryWithWhereAndWithOrCompositeExpression()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->andExpression(new Comparison(new Path('user'), Comparison::IN, [1, 2, 3, 4, 5]));
            $criteria->orExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
        });

        $query = $this->em->getRepository(CmsAddress::class)->createQueryBuilder('address')
            ->select('address.id')
            ->where('address.country = :country')
            ->setParameter('country', 'US')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_addresses c0_'
            . ' WHERE c0_.country = ? AND (c0_.user_id IN (1, 2, 3, 4, 5) OR c0_.organization_id = 1)';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerQueryWithMultiplyWhereAndWithComplicatedCompositeExpression()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->andExpression(
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new Comparison(new Path('user'), Comparison::NIN, [1, 2, 3, 4, 5]),
                        new Comparison(new Path('user'), Comparison::GTE, 20)
                    ]
                )
            );
            $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
        });

        $query = $this->em->getRepository(CmsAddress::class)->createQueryBuilder('address')
            ->select('address.id')
            ->where('address.country = :country')
            ->orWhere('address.zip = :zip')
            ->setParameter('country', 'US')
            ->setParameter('zip', '61000')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_addresses c0_'
            . ' WHERE (c0_.country = ? OR c0_.zip = ?)'
            . ' AND (c0_.user_id NOT IN (1, 2, 3, 4, 5) OR c0_.user_id >= 20)'
            . ' AND c0_.organization_id = 1';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerQueryWithJoinByPathAndWithComplicatedCompositeExpression()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsAddress::class) {
                $criteria->andExpression(
                    new CompositeExpression(
                        CompositeExpression::TYPE_OR,
                        [
                            new Comparison(new Path('user'), Comparison::LT, 5),
                            new Comparison(new Path('user'), Comparison::NEQ, 85)
                        ]
                    )
                );
            }
            $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
        });

        $query = $this->em->getRepository(CmsUser::class)->createQueryBuilder('user')
            ->select('user.id, address.country')
            ->join('user.address', 'address', 'WITH', 'address.id > 0')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0, c1_.country AS country_1'
            . ' FROM cms_users c0_'
            . ' INNER JOIN cms_addresses c1_'
            . ' ON c0_.id = c1_.user_id AND (c1_.id > 0'
            . ' AND (c1_.user_id < 5 OR c1_.user_id <> 85) AND c1_.organization_id = 1)'
            . ' WHERE c0_.organization_id = 1';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerQueryWithJoinByPathAndWithAccessDeniedInJoin()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsAddress::class) {
                $criteria->andExpression(new AccessDenied());
            } else {
                $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
            }
        });

        $query = $this->em->getRepository(CmsUser::class)->createQueryBuilder('user')
            ->select('user.id, address.country')
            ->join('user.address', 'address', 'WITH', 'address.id > 0')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0, c1_.country AS country_1'
            . ' FROM cms_users c0_'
            . ' INNER JOIN cms_addresses c1_'
            . ' ON c0_.id = c1_.user_id AND (c1_.id > 0 AND 1 = 0)'
            . ' WHERE c0_.organization_id = 1';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerQueryWithJoin()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsAddress::class) {
                $criteria->andExpression(new Comparison(new Path('user'), Comparison::LT, 5));
            }

            $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
        });

        $query = $this->em->getRepository(CmsUser::class)->createQueryBuilder('user')
            ->select('user.id, address.country')
            ->join(CmsAddress::class, 'address', 'WITH', 'address.user = user.id AND address = 1')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0, c1_.country AS country_1'
            . ' FROM cms_users c0_'
            . ' INNER JOIN cms_addresses c1_'
            . ' ON (c1_.user_id = c0_.id AND c1_.id = 1 AND c1_.user_id < 5 AND c1_.organization_id = 1)'
            . ' WHERE c0_.organization_id = 1';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerQueryWithJoinAndDisabledCheckRootEntity()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsAddress::class) {
                $criteria->andExpression(new Comparison(new Path('user'), Comparison::LT, 5));
            }

            $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
        });

        $query = $this->em->getRepository(CmsUser::class)->createQueryBuilder('user')
            ->select('user.id, address.country')
            ->join(CmsAddress::class, 'address', 'WITH', 'address.user = user.id AND address = 1')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0, c1_.country AS country_1'
            . ' FROM cms_users c0_'
            . ' INNER JOIN cms_addresses c1_'
            . ' ON (c1_.user_id = c0_.id AND c1_.id = 1'
            . ' AND c1_.user_id < 5 AND c1_.organization_id = 1)';

        $this->assertResultQueryEquals($expectedQuery, $query, [AclHelper::CHECK_ROOT_ENTITY => false]);
    }

    public function testWalkerQueryWithJoinAndDisabledCheckRelationships()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsAddress::class) {
                $criteria->andExpression(new Comparison(new Path('user'), Comparison::LT, 5));
            }

            $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
        });

        $query = $this->em->getRepository(CmsUser::class)->createQueryBuilder('user')
            ->select('user.id, address.country')
            ->join(CmsAddress::class, 'address', 'WITH', 'address.user = user.id AND address = 1')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0, c1_.country AS country_1'
            . ' FROM cms_users c0_'
            . ' INNER JOIN cms_addresses c1_'
            . ' ON (c1_.user_id = c0_.id AND c1_.id = 1)'
            . ' WHERE c0_.organization_id = 1';

        $this->assertResultQueryEquals($expectedQuery, $query, [AclHelper::CHECK_RELATIONS => false]);
    }

    public function testWalkerQueryWithSubselect()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsAddress::class) {
                $criteria->andExpression(new Comparison(new Path('user'), Comparison::LT, 5));
            }

            $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
        });

        $qb = $this->em->getRepository(CmsUser::class)->createQueryBuilder('u');
        $query = $qb->select('u.id')
            ->where(
                $qb->expr()->in(
                    'u.id',
                    'SELECT users.id FROM ' . CmsUser::class . ' users
                       JOIN users.articles articles
                       WHERE articles.id in (1,2,3)
                    '
                )
            )
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_users c0_'
            . ' WHERE c0_.id IN '
            . '(SELECT c1_.id FROM cms_users c1_'
            . ' INNER JOIN cms_articles c2_ ON c1_.id = c2_.user_id'
            . ' AND (c2_.organization_id = 1) WHERE c2_.id IN (1, 2, 3) AND c1_.organization_id = 1)'
            . ' AND c0_.organization_id = 1';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerQueryWithSubselectAndMultipleWhereConditions()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsAddress::class) {
                $criteria->andExpression(new Comparison(new Path('user'), Comparison::LT, 5));
            }

            $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
        });

        $qb = $this->em->getRepository(CmsUser::class)->createQueryBuilder('u');
        $query = $qb->select('u.id')
            ->where('u.id > 0')
            ->andWhere(
                '(EXISTS(
                    SELECT users.id FROM ' . CmsUser::class . ' users
                    JOIN users.articles articles
                    WHERE articles.id in (1,2,3)
                ))'
            )
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_users c0_'
            . ' WHERE c0_.id > 0'
            . ' AND (EXISTS '
            . '(SELECT c1_.id FROM cms_users c1_'
            . ' INNER JOIN cms_articles c2_ ON c1_.id = c2_.user_id'
            . ' AND (c2_.organization_id = 1) WHERE c2_.id IN (1, 2, 3) AND c1_.organization_id = 1))'
            . ' AND c0_.organization_id = 1';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerQueryWithSubselectAndDisabledCheckRootEntityAndCheckRelationships()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsAddress::class) {
                $criteria->andExpression(new Comparison(new Path('user'), Comparison::LT, 5));
            }

            $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
        });

        $context = new AccessRuleWalkerContext($this->accessRuleExecutor, 'VIEW', CmsUser::class, 9);
        $context->setOption(AclHelper::CHECK_ROOT_ENTITY, false);
        $context->setOption(AclHelper::CHECK_RELATIONS, false);
        $originalContext = clone $context;

        $qb = $this->em->getRepository(CmsUser::class)->createQueryBuilder('u');
        $query = $qb->select('u.id')
            ->join('u.address', 'a')
            ->where(
                $qb->expr()->in(
                    'u.id',
                    'SELECT users.id FROM ' . CmsUser::class . ' users
                       JOIN users.articles articles
                       WHERE articles.id in (1,2,3)
                    '
                )
            )
            ->getQuery();

        $query->setHint(
            Query::HINT_CUSTOM_TREE_WALKERS,
            [AccessRuleWalker::class]
        );
        $query->setHint(AccessRuleWalker::CONTEXT, $context);

        $this->assertEquals(
            'SELECT c0_.id AS id_0'
            . ' FROM cms_users c0_'
            . ' INNER JOIN cms_addresses c1_ ON c0_.id = c1_.user_id'
            . ' WHERE c0_.id IN '
            . '(SELECT c2_.id FROM cms_users c2_'
            . ' INNER JOIN cms_articles c3_ ON c2_.id = c3_.user_id AND (c3_.organization_id = 1)'
            . ' WHERE c3_.id IN (1, 2, 3) AND c2_.organization_id = 1'
            . ')',
            $query->getSQL()
        );

        // test that the context is not changed (it should be cloned for subqueries)
        $this->assertEquals($originalContext, $context);
    }

    public function testWalkerQueryWithSubselectAccessRule()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsArticle::class) {
                $subqueryCriteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'users');
                $subqueryCriteria->andExpression(
                    new Comparison(new Path('user', 'article'), Comparison::EQ, new Path('id', 'users'))
                );
                $subqueryCriteria->andExpression(
                    new Comparison(new Path('name', 'users'), Comparison::EQ, 'test')
                );

                $criteria->andExpression(new Comparison(new Path('user'), Comparison::IN, [1, 2, 3, 4, 5]));
                $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
                $criteria->orExpression(
                    new Exists(
                        new Subquery(
                            CmsUser::class,
                            'users',
                            $subqueryCriteria
                        )
                    )
                );
                $criteria->andExpression(new Comparison(new Path('user'), Comparison::LT, 5));
            }

            if ($criteria->getEntityClass() === CmsUser::class) {
                $criteria->andExpression(new Comparison(new Path('status'), Comparison::EQ, 'enabled'));
            }
        });

        $query = $this->em->getRepository(CmsArticle::class)->createQueryBuilder('article')
            ->select('article.id')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_articles c0_'
            . ' WHERE ((c0_.user_id IN (1, 2, 3, 4, 5) AND c0_.organization_id = 1)'
            . ' OR EXISTS (SELECT 1 AS sclr_1 FROM cms_users c1_'
            . ' WHERE c0_.user_id = c1_.id AND c1_.name = \'test\' AND c1_.status = \'enabled\'))'
            . ' AND c0_.user_id < 5';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerQueryWithNotExistsSubselectAccessRule()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsArticle::class) {
                $SubqueryCriteria = new Criteria(AccessRuleWalker::ORM_RULES_TYPE, CmsUser::class, 'users');
                $SubqueryCriteria->andExpression(
                    new Comparison(new Path('user', 'article'), Comparison::EQ, new Path('id', 'users'))
                );
                $SubqueryCriteria->andExpression(
                    new Comparison(new Path('name', 'users'), Comparison::EQ, 'test')
                );

                $criteria->andExpression(new Comparison(new Path('user'), Comparison::IN, [1, 2, 3, 4, 5]));
                $criteria->orExpression(
                    new Exists(
                        new Subquery(
                            CmsUser::class,
                            'users',
                            $SubqueryCriteria
                        ),
                        true
                    )
                );
                $criteria->orExpression(new Comparison(new Path('user'), Comparison::LT, 5));
            }
            if ($criteria->getEntityClass() === CmsUser::class) {
                $criteria->andExpression(new Comparison(new Path('status'), Comparison::EQ, 'enabled'));
            }
        });

        $query = $this->em->getRepository(CmsArticle::class)->createQueryBuilder('article')
            ->select('article.id')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_articles c0_'
            . ' WHERE (c0_.user_id IN (1, 2, 3, 4, 5)'
            . ' OR NOT EXISTS'
            . ' (SELECT 1 AS sclr_1 FROM cms_users c1_'
            . ' WHERE c0_.user_id = c1_.id AND c1_.name = \'test\' AND c1_.status = \'enabled\'))'
            . ' OR c0_.user_id < 5';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerQueryWithNullComparisonExpression()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->andExpression(new NullComparison(new Path('user')));
            $criteria->orExpression(new Comparison(new Path('user'), Comparison::LT, 5));
        });

        $query = $this->em->getRepository(CmsAddress::class)->createQueryBuilder('address')
            ->select('address.id')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0 FROM cms_addresses c0_ WHERE c0_.user_id IS NULL OR c0_.user_id < 5';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerQueryWithNotNullComparisonExpression()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->andExpression(new NullComparison(new Path('user'), true));
            $criteria->andExpression(new Comparison(new Path('user'), Comparison::LT, 5));
        });

        $query = $this->em->getRepository(CmsAddress::class)->createQueryBuilder('address')
            ->select('address.id')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0 FROM cms_addresses c0_'
            . ' WHERE c0_.user_id IS NOT NULL AND c0_.user_id < 5';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerWithAssociationRuleExpressionWithWrongParameter()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            "Parameter of Association expression should be the name of existing association for alias 'address'."
            . " Given name: 'city'."
        );

        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsAddress::class) {
                $criteria->andExpression(new Association('city'));
            }
        });

        $context = new AccessRuleWalkerContext($this->accessRuleExecutor, 'VIEW', CmsUser::class, 1);
        $query = $this->em->getRepository(CmsAddress::class)->createQueryBuilder('address')->getQuery();
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [AccessRuleWalker::class]);
        $query->setHint(AccessRuleWalker::CONTEXT, $context);
        $query->getSQL();
    }

    public function testWalkerWithAssociationRuleExpressionWithOneToManyParameter()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            "Parameter of Association expression should be to-one association. Given name: 'articles'."
        );

        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsUser::class) {
                $criteria->andExpression(new Association('articles'));
            }
        });

        $context = new AccessRuleWalkerContext($this->accessRuleExecutor, 'VIEW', CmsUser::class, 1);
        $query = $this->em->getRepository(CmsUser::class)->createQueryBuilder('u')->getQuery();
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [AccessRuleWalker::class]);
        $query->setHint(AccessRuleWalker::CONTEXT, $context);
        $query->getSQL();
    }

    public function testWalkerWithAssociationRuleExpressionOnSimpleQuery()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsAddress::class) {
                $criteria->andExpression(new Association('user'));
            }
            if ($criteria->getEntityClass() === CmsUser::class) {
                $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
                $criteria->andExpression(new Comparison(new Path('status'), Comparison::EQ, 'enabled'));
            }
        });

        $query = $this->em->getRepository(CmsAddress::class)->createQueryBuilder('address')
            ->select('address.id')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_addresses c0_'
            . ' WHERE EXISTS (SELECT 1 AS sclr_1 FROM cms_users c1_'
            . ' WHERE c1_.id = c0_.user_id AND c1_.organization_id = 1 AND c1_.status = \'enabled\')';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerWithAssociationRuleExpressionInJoinByPath()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsAddress::class) {
                $criteria->andExpression(new Association('user'));
            }
            if ($criteria->getEntityClass() === CmsUser::class) {
                $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
                $criteria->andExpression(new Comparison(new Path('status'), Comparison::EQ, 'enabled'));
            }
        });

        $query = $this->em->getRepository(CmsUser::class)->createQueryBuilder('user')
            ->select('user.id, address.country')
            ->join('user.address', 'address', 'WITH', 'address.id > 0')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0, c1_.country AS country_1'
            . ' FROM cms_users c0_'
            . ' INNER JOIN cms_addresses c1_ ON c0_.id = c1_.user_id AND (c1_.id > 0 AND'
            . ' EXISTS (SELECT 1 AS sclr_2 FROM cms_users c2_ WHERE c2_.id = c1_.user_id'
            . ' AND c2_.organization_id = 1 AND c2_.status = \'enabled\'))'
            . ' WHERE c0_.organization_id = 1 AND c0_.status = \'enabled\'';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerWithAssociationRuleExpressionInJoin()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsAddress::class) {
                $criteria->andExpression(new Association('user'));
            }
            if ($criteria->getEntityClass() === CmsUser::class) {
                $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
                $criteria->andExpression(new Comparison(new Path('status'), Comparison::EQ, 'enabled'));
            }
        });

        $query = $this->em->getRepository(CmsUser::class)->createQueryBuilder('user')
            ->select('user.id, address.country')
            ->join(CmsAddress::class, 'address', 'WITH', 'address.user = user.id AND address = 1')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0, c1_.country AS country_1'
            . ' FROM cms_users c0_'
            . ' INNER JOIN cms_addresses c1_ ON'
            . ' (c1_.user_id = c0_.id AND c1_.id = 1 AND'
            . ' EXISTS (SELECT 1 AS sclr_2 FROM cms_users c2_ WHERE c2_.id = c1_.user_id'
            . ' AND c2_.organization_id = 1 AND c2_.status = \'enabled\'))'
            . ' WHERE c0_.organization_id = 1 AND c0_.status = \'enabled\'';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerWithAssociationRuleExpressionOnQueryWithSubselect()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsUser::class) {
                $criteria->andExpression(new Association('organization'));
                $criteria->andExpression(new Comparison(new Path('status'), Comparison::EQ, 'enabled'));
            }
            if ($criteria->getEntityClass() === CmsOrganization::class) {
                $criteria->andExpression(new Comparison(new Path('text'), Comparison::EQ, 'test'));
            }
        });

        $qb = $this->em->getRepository(CmsArticle::class)->createQueryBuilder('a');
        $query = $qb->select('a.id')
            ->where($qb->expr()->in('a.user', 'SELECT users.id FROM ' . CmsUser::class . ' users'))
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_articles c0_'
            . ' WHERE c0_.user_id IN ('
            . 'SELECT c1_.id FROM cms_users c1_'
            . ' WHERE EXISTS (SELECT 1 AS sclr_1 FROM cms_organization c2_'
            . ' WHERE c2_.id = c1_.organization_id AND c2_.text = \'test\')'
            . ' AND c1_.status = \'enabled\')';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerQueryWithSubselectInJoin()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsAddress::class) {
                $criteria->andExpression(new Comparison(new Path('user'), Comparison::LT, 5));
            }

            $criteria->andExpression(new Comparison(new Path('organization'), Comparison::EQ, 1));
        });

        $qb = $this->em->getRepository(CmsArticle::class)->createQueryBuilder('article');
        $query = $qb->select('article.id')
            ->join(
                'article.user',
                'u',
                'WITH',
                $qb->expr()->in(
                    'u.id',
                    'SELECT users.id FROM ' . CmsUser::class . ' users
                       JOIN users.articles articles
                       WHERE articles.id in (1,2,3)
                    '
                )
            )
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_articles c0_'
            . ' INNER JOIN cms_users c1_ ON'
            . ' c0_.user_id = c1_.id AND ('
            . 'c1_.id IN ('
            . 'SELECT c2_.id FROM cms_users c2_'
            . ' INNER JOIN cms_articles c3_ ON c2_.id = c3_.user_id AND (c3_.organization_id = 1)'
            . ' WHERE c3_.id IN (1, 2, 3) AND c2_.organization_id = 1'
            . ') AND c1_.organization_id = 1)'
            . ' WHERE c0_.organization_id = 1';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerWithAssociationRuleExpressionOnInverseMappedRelation()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            if ($criteria->getEntityClass() === CmsUser::class) {
                $criteria->andExpression(new Association('address'));
            }
            if ($criteria->getEntityClass() === CmsAddress::class) {
                $criteria->andExpression(new Comparison(new Path('country'), Comparison::EQ, 'US'));
            }
        });

        $query = $this->em->getRepository(CmsUser::class)->createQueryBuilder('user')
            ->select('user.id')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_users c0_'
            . ' WHERE EXISTS (SELECT 1 AS sclr_1 FROM cms_addresses c1_'
            . ' WHERE c0_.id = c1_.user_id AND c1_.country = \'US\')';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerWithContainsExpressionForStringField()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->andExpression(new Comparison(new Path('topic'), Comparison::CONTAINS, 'test'));
        });

        $query = $this->em->getRepository(CmsArticle::class)->createQueryBuilder('e')
            ->select('e.id')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0 FROM cms_articles c0_ WHERE c0_.topic LIKE \'%test%\'';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerWithContainsExpressionForStringFieldForNotStringValue()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The right operand for string CONTAINS comparison must be a string.');

        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->andExpression(new Comparison(new Path('topic'), Comparison::CONTAINS, null));
        });

        $query = $this->em->getRepository(CmsArticle::class)->createQueryBuilder('e')
            ->select('e.id')
            ->getQuery();

        $this->prepareQuery($query);
        $query->getSQL();
    }

    public function testWalkerWithContainsExpressionForJsonArrayField()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->andExpression(new Comparison(new Path('types'), Comparison::CONTAINS, 'val'));
        });

        $query = $this->em->getRepository(CmsArticle::class)->createQueryBuilder('e')
            ->select('e.id')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_articles c0_'
            . ' WHERE c0_.types LIKE \'%"val"%\'';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerWithContainsExpressionWithOneValueInArrayForJsonArrayField()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->andExpression(new Comparison(new Path('types'), Comparison::CONTAINS, 'val'));
        });

        $query = $this->em->getRepository(CmsArticle::class)->createQueryBuilder('e')
            ->select('e.id')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_articles c0_'
            . ' WHERE c0_.types LIKE \'%"val"%\'';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerWithContainsExpressionWithSeveralValuesForJsonArrayField()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->andExpression(new Comparison(new Path('types'), Comparison::CONTAINS, ['val1', 'val2']));
        });

        $query = $this->em->getRepository(CmsArticle::class)->createQueryBuilder('e')
            ->select('e.id')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_articles c0_'
            . ' WHERE (c0_.types LIKE \'%"val1"%\' OR c0_.types LIKE \'%"val2"%\')';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerWithContainsExpressionWithSeveralValuesForJsonArrayFieldAndWhereNotEmpty()
    {
        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->andExpression(new Comparison(new Path('types'), Comparison::CONTAINS, ['val1', 'val2']));
        });

        $query = $this->em->getRepository(CmsArticle::class)->createQueryBuilder('e')
            ->select('e.id')
            ->where('e.id > 0')
            ->getQuery();

        $expectedQuery = 'SELECT c0_.id AS id_0'
            . ' FROM cms_articles c0_'
            . ' WHERE c0_.id > 0 AND (c0_.types LIKE \'%"val1"%\' OR c0_.types LIKE \'%"val2"%\')';

        $this->assertResultQueryEquals($expectedQuery, $query);
    }

    public function testWalkerWithContainsExpressionWithNoValuesInArrayForJsonArrayField()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The right operand for JSON array CONTAINS comparison must be a string or not empty array.'
        );

        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->andExpression(new Comparison(new Path('types'), Comparison::CONTAINS, []));
        });

        $query = $this->em->getRepository(CmsArticle::class)->createQueryBuilder('e')
            ->select('e.id')
            ->where('e.id > 0')
            ->getQuery();

        $this->prepareQuery($query);
        $query->getSQL();
    }

    public function testWalkerWithContainsExpressionWithNotStringAndNotArrayValueForJsonArrayField()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The right operand for JSON array CONTAINS comparison must be a string or not empty array.'
        );

        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->andExpression(new Comparison(new Path('types'), Comparison::CONTAINS, null));
        });

        $query = $this->em->getRepository(CmsArticle::class)->createQueryBuilder('e')
            ->select('e.id')
            ->where('e.id > 0')
            ->getQuery();

        $this->prepareQuery($query);
        $query->getSQL();
    }

    public function testWalkerWithContainsExpressionForJsonArrayFieldWhenLeftOperandIsNotPath()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The left operand for CONTAINS comparison must be a path.');

        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->andExpression(new Comparison('val', Comparison::CONTAINS, new Path('types')));
        });

        $query = $this->em->getRepository(CmsArticle::class)->createQueryBuilder('e')
            ->select('e.id')
            ->where('e.id > 0')
            ->getQuery();

        $this->prepareQuery($query);
        $query->getSQL();
    }

    public function testWalkerWithContainsExpressionForJsonArrayFieldWhenRightOperandIsNotValue()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The left operand for CONTAINS comparison must be a value.');

        $this->rule->setRule(function (Criteria $criteria) {
            $criteria->andExpression(new Comparison(new Path('types'), Comparison::CONTAINS, new Path('types')));
        });

        $query = $this->em->getRepository(CmsArticle::class)->createQueryBuilder('e')
            ->select('e.id')
            ->where('e.id > 0')
            ->getQuery();

        $this->prepareQuery($query);
        $query->getSQL();
    }
}
