<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\Common\Collections\Criteria as CommonCriteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\DBAL\Platforms\Keywords\PostgreSQL100Keywords;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitorFactory;
use Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression as Expression;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use Oro\Bundle\ApiBundle\Util\CriteriaNormalizer;
use Oro\Bundle\ApiBundle\Util\CriteriaPlaceholdersResolver;
use Oro\Bundle\ApiBundle\Util\FieldDqlExpressionProviderInterface;
use Oro\Bundle\ApiBundle\Util\OptimizeJoinsDecisionMaker;
use Oro\Bundle\ApiBundle\Util\OptimizeJoinsFieldVisitorFactory;
use Oro\Bundle\ApiBundle\Util\RequireJoinsDecisionMaker;
use Oro\Bundle\ApiBundle\Util\RequireJoinsFieldVisitorFactory;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Component\Testing\Unit\ORM\Mocks\ConnectionMock;
use Oro\Component\Testing\Unit\ORM\Mocks\DatabasePlatformMock;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CriteriaConnectorTest extends OrmRelatedTestCase
{
    private const string ENTITY_NAMESPACE = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\\';
    private FieldDqlExpressionProviderInterface&MockObject $fieldDqlExpressionProvider;
    private ConfigManager&MockObject $configManager;
    private EntityClassResolver $entityClassResolver;
    private CriteriaConnector $criteriaConnector;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $platform = new DatabasePlatformMock();
        $platform->setReservedKeywordsClass(PostgreSQL100Keywords::class);
        /** @var ConnectionMock $connection */
        $connection = $this->em->getConnection();
        $connection->setDatabasePlatform($platform);

        $this->fieldDqlExpressionProvider = $this->createMock(FieldDqlExpressionProviderInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->entityClassResolver = new EntityClassResolver($this->doctrine);

        $expressionVisitorFactory = new QueryExpressionVisitorFactory(
            [
                'NOT' => new Expression\NotCompositeExpression(),
                'AND' => new Expression\AndCompositeExpression(),
                'OR'  => new Expression\OrCompositeExpression()
            ],
            [
                '='                 => new Expression\EqComparisonExpression(),
                '<>'                => new Expression\NeqComparisonExpression(),
                'IN'                => new Expression\InComparisonExpression(),
                'NEQ_OR_NULL'       => new Expression\NeqOrNullComparisonExpression(),
                'NEQ_OR_EMPTY'      => new Expression\NeqOrEmptyComparisonExpression(),
                'EXISTS'            => new Expression\ExistsComparisonExpression(),
                'EMPTY'             => new Expression\EmptyComparisonExpression(),
                'MEMBER_OF'         => new Expression\MemberOfComparisonExpression(),
                'ALL_MEMBER_OF'     => new Expression\AllMemberOfComparisonExpression(),
                'ALL_NOT_MEMBER_OF' => new Expression\AllMemberOfComparisonExpression(true)
            ],
            $this->fieldDqlExpressionProvider,
            $this->entityClassResolver
        );

        $this->criteriaConnector = new CriteriaConnector(
            new CriteriaNormalizer(
                $this->doctrineHelper,
                new RequireJoinsFieldVisitorFactory(new RequireJoinsDecisionMaker()),
                new OptimizeJoinsFieldVisitorFactory(new OptimizeJoinsDecisionMaker()),
                $this->configManager
            ),
            new CriteriaPlaceholdersResolver(),
            $expressionVisitorFactory,
            $this->fieldDqlExpressionProvider,
            $this->entityClassResolver
        );
    }

    private function assertQuery(
        CommonCriteria $criteria,
        string $expectedDql,
        string $rootEntityClass = Entity\User::class
    ): void {
        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from($rootEntityClass, 'e');

        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertEquals(
            $expectedDql,
            $this->prepareDqlToCompare($qb->getDQL())
        );
    }

    private function prepareDqlToCompare(string $dql): string
    {
        return str_replace([self::ENTITY_NAMESPACE, EnumOption::class], ['Test:', 'Test:EnumOption'], $dql);
    }

    private static function comparison(string $field, string $operator, mixed $value): Comparison
    {
        return new Comparison($field, $operator, $value);
    }

    private function createCriteria(bool $isCommonCriteria = false): CommonCriteria|Criteria
    {
        if ($isCommonCriteria) {
            return new CommonCriteria();
        }

        return new Criteria($this->entityClassResolver);
    }

    public function criteriaDataProvider(): array
    {
        return [
            [false],
            [true]
        ];
    }

    public function testOrderBy(): void
    {
        $criteria = $this->createCriteria();
        $criteria->orderBy(['id' => Criteria::ASC, 'category.name' => Criteria::ASC]);

        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $this->fieldDqlExpressionProvider->expects(self::exactly(2))
            ->method('getFieldDqlExpression')
            ->with(self::identicalTo($qb))
            ->willReturn(null);

        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertEquals(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category category'
            . ' ORDER BY e.id ASC, category.name ASC',
            $this->prepareDqlToCompare($qb->getDQL())
        );
    }

    public function testOrderByAssociation(): void
    {
        $criteria = $this->createCriteria();
        $criteria->orderBy(['id' => Criteria::ASC, 'category' => Criteria::ASC]);

        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $this->fieldDqlExpressionProvider->expects(self::exactly(2))
            ->method('getFieldDqlExpression')
            ->with(self::identicalTo($qb))
            ->willReturn(null);

        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertEquals(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category category'
            . ' ORDER BY e.id ASC, e.category ASC',
            $this->prepareDqlToCompare($qb->getDQL())
        );
    }

    public function testOrderByForFieldWithDqlExpression(): void
    {
        $criteria = $this->createCriteria();
        $criteria->orderBy(['id' => Criteria::ASC, 'category.name' => Criteria::ASC]);

        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $this->fieldDqlExpressionProvider->expects(self::exactly(2))
            ->method('getFieldDqlExpression')
            ->with(self::identicalTo($qb))
            ->willReturnMap([
                [$qb, 'id', null],
                [$qb, 'category.name', 'UPPER(category.name)'],
            ]);

        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertEquals(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category category'
            . ' ORDER BY e.id ASC, UPPER(category.name) ASC',
            $this->prepareDqlToCompare($qb->getDQL())
        );
    }

    public function testOrderByEnumField(): void
    {
        $criteria = $this->createCriteria();
        $criteria->orderBy(['enumField' => Criteria::ASC]);

        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(Entity\User::class, 'enumField')
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getId')
            ->with('extend', Entity\User::class, 'enumField')
            ->willReturn(new FieldConfigId('extend', Entity\User::class, 'enumField', 'enum'));

        $this->fieldDqlExpressionProvider->expects(self::once())
            ->method('getFieldDqlExpression')
            ->with(self::identicalTo($qb))
            ->willReturn("JSON_EXTRACT(e.serialized_data, 'enumField')");

        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertEquals(
            'SELECT e FROM Test:User e'
            . ' ORDER BY JSON_EXTRACT(e.serialized_data, \'enumField\') ASC',
            $this->prepareDqlToCompare($qb->getDQL())
        );
    }

    public function testOrderByMultiEnumField(): void
    {
        $criteria = $this->createCriteria();
        $criteria->orderBy(['enumField' => Criteria::ASC]);

        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->with(Entity\User::class, 'enumField')
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('getId')
            ->with('extend', Entity\User::class, 'enumField')
            ->willReturn(new FieldConfigId('extend', Entity\User::class, 'enumField', 'multiEnum'));

        $this->fieldDqlExpressionProvider->expects(self::once())
            ->method('getFieldDqlExpression')
            ->with(self::identicalTo($qb))
            ->willReturn(sprintf(
                "JSONB_ARRAY_CONTAINS_JSON(e.serialized_data, 'enumField', CONCAT('\"', {entity:%s}.id, '\"')) = true",
                EnumOption::class
            ));

        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertEquals(
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN Test:EnumOption enumField WITH'
            . ' JSONB_ARRAY_CONTAINS_JSON(e.serialized_data, \'enumField\', CONCAT(\'"\', enumField.id, \'"\')) = true'
            . ' ORDER BY JSONB_ARRAY_CONTAINS_JSON(e.serialized_data, \'enumField\','
            . ' CONCAT(\'"\', {entity:Test:EnumOption}.id, \'"\')) = true ASC',
            $this->prepareDqlToCompare($qb->getDQL())
        );
    }

    public function testWhere(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category.name', 'test_category'),
                $criteria::expr()->eq('groups.name', 'test_group')
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE category.name = :category_name AND groups.name = :groups_name'
        );
    }

    public function testWhereWhenJoinAliasEqualsToDatabaseKeyword(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('group.name', 'test_group')
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:Contact e'
            . ' INNER JOIN e.group group1'
            . ' WHERE group1.name = :group1_name',
            Entity\Contact::class
        );
    }

    public function testWhereByAssociation(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category', 'test_category'),
                $criteria::expr()->eq('groups', 'test_group')
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE e.category = :e_category AND e.groups = :e_groups'
        );
    }

    public function testWhereByEnumField(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere($criteria::expr()->eq('enumField', 'test'));

        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(Entity\User::class, 'enumField')
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getId')
            ->with('extend', Entity\User::class, 'enumField')
            ->willReturn(new FieldConfigId('extend', Entity\User::class, 'enumField', 'enum'));

        $this->fieldDqlExpressionProvider->expects(self::once())
            ->method('getFieldDqlExpression')
            ->with(self::identicalTo($qb))
            ->willReturn("JSON_EXTRACT(e.serialized_data, 'enumField')");

        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertEquals(
            'SELECT e FROM Test:User e'
            . ' WHERE JSON_EXTRACT(e.serialized_data, \'enumField\') = :e_enumField',
            $this->prepareDqlToCompare($qb->getDQL())
        );
    }

    public function testWhereByMultiEnumField(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere($criteria::expr()->memberOf('enumField', ['test']));

        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $this->configManager->expects(self::exactly(2))
            ->method('hasConfig')
            ->with(Entity\User::class, 'enumField')
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('getId')
            ->with('extend', Entity\User::class, 'enumField')
            ->willReturn(new FieldConfigId('extend', Entity\User::class, 'enumField', 'multiEnum'));

        $this->fieldDqlExpressionProvider->expects(self::once())
            ->method('getFieldDqlExpression')
            ->with(self::identicalTo($qb))
            ->willReturn(sprintf(
                "JSONB_ARRAY_CONTAINS_JSON(e.serialized_data, 'enumField', CONCAT('\"', {entity:%s}.id, '\"')) = true",
                EnumOption::class
            ));

        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertEquals(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN Test:EnumOption enumField WITH'
            . ' JSONB_ARRAY_CONTAINS_JSON(e.serialized_data, \'enumField\', CONCAT(\'"\', enumField.id, \'"\')) = true'
            . ' WHERE EXISTS('
            . 'SELECT enumField_subquery1 FROM Test:EnumOption enumField_subquery1'
            . ' WHERE JSONB_ARRAY_CONTAINS_JSON(e.serialized_data, \'enumField\','
            . ' CONCAT(\'"\', enumField_subquery1.id, \'"\')) = true AND enumField_subquery1 IN(:e_enumField))',
            $this->prepareDqlToCompare($qb->getDQL())
        );
    }

    public function testShouldOptimizeJoinForExists(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                self::comparison('category.name', 'EXISTS', true),
                $criteria::expr()->eq('groups.name', 'test_group')
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE category.name IS NOT NULL AND groups.name = :groups_name'
        );
    }

    public function testShouldNotOptimizeJoinForNotExists(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                self::comparison('category.name', 'EXISTS', false),
                $criteria::expr()->eq('groups.name', 'test_group')
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE category.name IS NULL AND groups.name = :groups_name'
        );
    }

    public function testShouldNotOptimizeJoinForNeqOrNull(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                self::comparison('category.name', 'NEQ_OR_NULL', new Value('test_category')),
                $criteria::expr()->eq('groups.name', 'test_group')
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE (category.name NOT IN(:category_name) OR category.name IS NULL)'
            . ' AND groups.name = :groups_name'
        );
    }

    public function testShouldNotOptimizeJoinForNeqOrEmpty(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category', 'test_category'),
                $criteria::expr()->in('groups', [123]),
                self::comparison('groups', 'NEQ_OR_EMPTY', 234)
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' LEFT JOIN e.groups groups'
            . ' WHERE e.category = :e_category'
            . ' AND e.groups IN(:e_groups)'
            . ' AND (NOT(EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 = groups AND groups_subquery1 IN(:e_groups_2))))'
        );
    }

    public function testShouldNotOptimizeJoinForEmpty(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category', 'test_category'),
                $criteria::expr()->in('groups', [123]),
                self::comparison('groups', 'EMPTY', true)
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' LEFT JOIN e.groups groups'
            . ' WHERE e.category = :e_category'
            . ' AND e.groups IN(:e_groups)'
            . ' AND NOT(EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 = groups))'
        );
    }

    public function testShouldOptimizeJoinForNotEmpty(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category', 'test_category'),
                $criteria::expr()->in('groups', [123]),
                self::comparison('groups', 'EMPTY', false)
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' INNER JOIN e.groups groups'
            . ' WHERE e.category = :e_category'
            . ' AND e.groups IN(:e_groups)'
            . ' AND EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 = groups)'
        );
    }

    public function testShouldNotOptimizeJoinForAllMemberOf(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category', 'test_category'),
                $criteria::expr()->in('groups', [123]),
                self::comparison('groups', 'ALL_MEMBER_OF', 234)
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' LEFT JOIN e.groups groups'
            . ' WHERE e.category = :e_category'
            . ' AND e.groups IN(:e_groups)'
            . ' AND (:e_groups_2_expected = ('
            . 'SELECT COUNT(groups_subquery1)'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups AND groups_subquery1 IN(:e_groups_2)))'
        );
    }

    public function testShouldNotOptimizeJoinForAllNotMemberOf(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category', 'test_category'),
                $criteria::expr()->in('groups', [123]),
                self::comparison('groups', 'ALL_NOT_MEMBER_OF', 234)
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' LEFT JOIN e.groups groups'
            . ' WHERE e.category = :e_category'
            . ' AND e.groups IN(:e_groups)'
            . ' AND (:e_groups_2_expected = ('
            . 'SELECT COUNT(groups_subquery1)'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups AND groups_subquery1 IN(:e_groups_2)))'
        );
    }

    public function testShouldNotRequireJoinForEmpty(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category', 'test_category'),
                self::comparison('groups', 'EMPTY', true)
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' WHERE e.category = :e_category'
            . ' AND NOT(EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups))'
        );
    }

    public function testShouldRequireJoinForNotEmpty(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('category', 'test_category'),
                self::comparison('groups', 'EMPTY', false)
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category category'
            . ' WHERE e.category = :e_category'
            . ' AND EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups)'
        );
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testShouldNotRequireAnyJoinWhenOnlyEmpty(bool $isCommonCriteria): void
    {
        $criteria = $this->createCriteria($isCommonCriteria);
        $criteria->andWhere(
            self::comparison('groups', 'EMPTY', true)
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' WHERE NOT(EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups))'
        );
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testShouldNotRequireAnyJoinsWhenOnlyNotEmpty(bool $isCommonCriteria): void
    {
        $criteria = $this->createCriteria($isCommonCriteria);
        $criteria->andWhere(
            self::comparison('groups', 'EMPTY', false)
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' WHERE EXISTS('
            . 'SELECT groups_subquery1'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups)'
        );
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testShouldNotRequireAnyJoinsWhenOnlyAllMemberOf(bool $isCommonCriteria): void
    {
        $criteria = $this->createCriteria($isCommonCriteria);
        $criteria->andWhere(
            self::comparison('groups', 'ALL_MEMBER_OF', 234)
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' WHERE :e_groups_expected = ('
            . 'SELECT COUNT(groups_subquery1)'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups AND groups_subquery1 IN(:e_groups))'
        );
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testShouldNotRequireAnyJoinsWhenOnlyAllNotMemberOf(bool $isCommonCriteria): void
    {
        $criteria = $this->createCriteria($isCommonCriteria);
        $criteria->andWhere(
            self::comparison('groups', 'ALL_NOT_MEMBER_OF', 234)
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' WHERE :e_groups_expected = ('
            . 'SELECT COUNT(groups_subquery1)'
            . ' FROM Test:Group groups_subquery1'
            . ' WHERE groups_subquery1 MEMBER OF e.groups AND groups_subquery1 IN(:e_groups))'
        );
    }

    public function testNestedFieldInOrderBy(): void
    {
        $criteria = $this->createCriteria();
        $criteria->orderBy(['id' => Criteria::ASC, 'products.category.name' => Criteria::ASC]);

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.products products'
            . ' LEFT JOIN products.category category'
            . ' ORDER BY e.id ASC, category.name ASC'
        );
    }

    public function testNestedAssociationInOrderBy(): void
    {
        $criteria = $this->createCriteria();
        $criteria->orderBy(['id' => Criteria::ASC, 'products.category' => Criteria::ASC]);

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.products products'
            . ' LEFT JOIN products.category category'
            . ' ORDER BY e.id ASC, products.category ASC'
        );
    }

    public function testNestedFieldInWhere(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->eq('products.category.name', 'test_category')
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.products products'
            . ' INNER JOIN products.category category'
            . ' WHERE category.name = :category_name'
        );
    }

    public function testNestedAssociationInWhere(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere(
            $criteria::expr()->eq('products.category', 'test_category')
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.products products'
            . ' INNER JOIN products.category category'
            . ' WHERE products.category = :products_category'
        );
    }

    public function testNestedFieldInOrderByAndJoinsAlreadyExist(): void
    {
        $criteria = $this->createCriteria();
        $criteria->addLeftJoin('products', '{root}.products')->setAlias('products');
        $criteria->addLeftJoin('products.category', '{products}.category')->setAlias('category');
        $criteria->orderBy(['products.category.name' => Criteria::ASC]);

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.products products'
            . ' LEFT JOIN products.category category'
            . ' ORDER BY category.name ASC'
        );
    }

    public function testNestedFieldInWhereAndJoinsAlreadyExist(): void
    {
        $criteria = $this->createCriteria();
        $criteria->addLeftJoin('products', '{root}.products')->setAlias('products');
        $criteria->addLeftJoin('products.category', '{products}.category')->setAlias('category');
        $criteria->andWhere(
            $criteria::expr()->eq('products.category.name', 'test_category')
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.products products'
            . ' INNER JOIN products.category category'
            . ' WHERE category.name = :category_name'
        );
    }

    public function testNestedFieldWithPlaceholderInWhereAndJoinsAlreadyExist(): void
    {
        $criteria = $this->createCriteria();
        $criteria->addLeftJoin('products', '{root}.products')->setAlias('products');
        $criteria->addLeftJoin('products.category', '{products}.category')->setAlias('category');
        $criteria->andWhere(
            $criteria::expr()->eq('{products.category}.name', 'test_category')
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.products products'
            . ' INNER JOIN products.category category'
            . ' WHERE category.name = :category_name'
        );
    }

    public function testPlaceholdersInOrderBy(): void
    {
        $criteria = $this->createCriteria();
        $criteria->addLeftJoin('category', '{root}.category');
        $criteria->addLeftJoin('products', '{root}.products');
        $criteria->addLeftJoin('products.category', '{products}.category');
        $criteria->addLeftJoin('{owner}', '{root}.owner');
        $criteria->orderBy(
            [
                '{root}.name'              => Criteria::ASC,
                '{category}.name'          => Criteria::ASC,
                '{products.category}.name' => Criteria::ASC,
                '{owner}'                  => Criteria::ASC
            ]
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.category alias1'
            . ' LEFT JOIN e.products alias2'
            . ' LEFT JOIN alias2.category alias3'
            . ' LEFT JOIN e.owner alias4'
            . ' ORDER BY e.name ASC, alias1.name ASC, alias3.name ASC, owner ASC'
        );
    }

    public function testPlaceholdersInWhere(): void
    {
        $criteria = $this->createCriteria();
        $criteria->addLeftJoin('category', '{root}.category');
        $criteria->addLeftJoin('products', '{root}.products');
        $criteria->addLeftJoin('products.category', '{products}.category');
        $criteria->addLeftJoin('{owner}', '{root}.owner');
        $criteria->andWhere(
            $criteria::expr()->andX(
                $criteria::expr()->eq('{root}.name', 'test_user'),
                $criteria::expr()->eq('{category}.name', 'test_category'),
                $criteria::expr()->eq('{products.category}.name', 'test_category'),
                $criteria::expr()->eq('{owner}', 'test_owner')
            )
        );

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' INNER JOIN e.category alias1'
            . ' INNER JOIN e.products alias2'
            . ' INNER JOIN alias2.category alias3'
            . ' INNER JOIN e.owner alias4'
            . ' WHERE e.name = :e_name AND alias1.name = :alias1_name'
            . ' AND alias3.name = :alias3_name AND owner = :owner'
        );
    }

    public function testAssociationsWithSameName(): void
    {
        $criteria = $this->createCriteria();
        $criteria->orderBy(['owner.owner.owner.name' => Criteria::ASC]);

        $this->assertQuery(
            $criteria,
            'SELECT e FROM Test:User e'
            . ' LEFT JOIN e.owner owner'
            . ' LEFT JOIN owner.owner owner1'
            . ' LEFT JOIN owner1.owner owner2'
            . ' ORDER BY owner2.name ASC'
        );
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testCriteriaWhenFirstResultIsNotSet(bool $isCommonCriteria): void
    {
        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $criteria = $this->createCriteria($isCommonCriteria);
        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertEquals(0, $qb->getFirstResult());
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testCriteriaWithFirstResult(bool $isCommonCriteria): void
    {
        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $criteria = $this->createCriteria($isCommonCriteria);
        $criteria->setFirstResult(12);

        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertSame(12, $qb->getFirstResult());
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testCriteriaWhenMaxResultsIsNotSet(bool $isCommonCriteria): void
    {
        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $criteria = $this->createCriteria($isCommonCriteria);
        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertNull($qb->getMaxResults());
    }

    /**
     * @dataProvider criteriaDataProvider
     */
    public function testCriteriaWithMaxResults(bool $isCommonCriteria): void
    {
        $qb = new QueryBuilder($this->em);
        $qb->select('e')->from(Entity\User::class, 'e');

        $criteria = $this->createCriteria($isCommonCriteria);
        $criteria->setMaxResults(3);

        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertSame(3, $qb->getMaxResults());
    }

    public function testPlaceholdersForManuallyJoinedEntity(): void
    {
        $criteria = $this->createCriteria();
        $criteria->andWhere($criteria::expr()->eq('{role}.name', 'test_role'));
        $criteria->andWhere($criteria::expr()->eq('{account}.name', 'test_account'));
        $criteria->andWhere($criteria::expr()->eq('name', 'test_user'));
        $criteria->orderBy(['{role}.name' => Criteria::ASC, '{group}.id' => Criteria::ASC]);

        $qb = new QueryBuilder($this->em);
        $qb
            ->select('e')
            ->from(Entity\User::class, 'e')
            ->innerJoin(Entity\Role::class, 'role', Join::WITH, '1 = 1')
            ->leftJoin(Entity\Group::class, 'group', Join::WITH, '1 = 1')
            ->leftJoin(Entity\Account::class, 'account', Join::WITH, '1 = 1')
            ->leftJoin('account.roles', 'account_roles');

        $this->criteriaConnector->applyCriteria($qb, $criteria);

        self::assertEquals(
            'SELECT e FROM Test:User e'
            . ' INNER JOIN Test:Role role WITH 1 = 1'
            . ' LEFT JOIN Test:Group group WITH 1 = 1'
            . ' INNER JOIN Test:Account account WITH 1 = 1'
            . ' LEFT JOIN account.roles account_roles'
            . ' WHERE (role.name = :role_name AND account.name = :account_name) AND e.name = :e_name'
            . ' ORDER BY role.name ASC, group.id ASC',
            $this->prepareDqlToCompare($qb->getDQL())
        );
    }
}
