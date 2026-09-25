<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Oro\Bundle\DataAuditBundle\Filter\AuditDataFilter;
use Oro\Bundle\DataAuditBundle\Provider\AuditTypeInterface;
use Oro\Bundle\DataAuditBundle\Provider\EntityAuditFieldSearchProvider;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Component\Exception\UnexpectedTypeException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;

class AuditDataFilterTest extends TestCase
{
    private array $matchingAuditTypeGroups = [];
    private array $matchingEntityFields = ['classes' => [], 'fields' => []];
    private AuditDataFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $auditTypes = $this->createMock(AuditTypeInterface::class);
        $auditTypes->expects(self::any())
            ->method('getMatchingFieldGroups')
            ->willReturnCallback(fn (): array => $this->matchingAuditTypeGroups);

        $entityFieldSearchProvider = $this->createMock(EntityAuditFieldSearchProvider::class);
        $entityFieldSearchProvider->expects(self::any())
            ->method('getMatchingFields')
            ->willReturnCallback(fn (): array => $this->matchingEntityFields);

        $this->filter = new AuditDataFilter(
            $this->createMock(FormFactoryInterface::class),
            new FilterUtility(),
            $auditTypes,
            $entityFieldSearchProvider
        );
        $this->filter->init('audit-data', [FilterUtility::DATA_NAME_KEY => 'a.id']);
    }

    public function testThrowsOnUnsupportedDatasource(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->filter->apply(
            $this->createMock(FilterDatasourceAdapterInterface::class),
            ['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'x']
        );
    }

    public function testDoesNotApplyOnEmptyValue(): void
    {
        $ds = $this->createDatasource();

        self::assertFalse($this->filter->apply($ds, ['type' => TextFilterType::TYPE_CONTAINS, 'value' => '']));
        self::assertSame('', $this->whereString($ds));
    }

    public function testAppliesExistsOverAuditFields(): void
    {
        $ds = $this->createDatasource();

        $result = $this->filter->apply($ds, ['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'promo']);

        self::assertTrue($result);
        $where = $this->whereString($ds);
        self::assertStringContainsStringIgnoringCase('EXISTS', $where);
        self::assertStringNotContainsString('NOT(', $where);
        self::assertStringContainsString(AuditField::class, $where);
        self::assertStringContainsString('.field', $where);
        self::assertStringContainsString('.oldText', $where);
        self::assertStringContainsString('.newText', $where);
        self::assertStringContainsString('%promo%', $where);
        self::assertStringNotContainsString('%%promo%%', $where);
    }

    public function testAppliesNegatedExistsForNotContains(): void
    {
        $ds = $this->createDatasource();

        $result = $this->filter->apply($ds, ['type' => TextFilterType::TYPE_NOT_CONTAINS, 'value' => 'promo']);

        self::assertTrue($result);
        self::assertStringContainsString('NOT(', $this->whereString($ds));
    }

    public function testMatchesUnscopedFieldsOfAnAuditType(): void
    {
        $this->matchingAuditTypeGroups = [
            ['classes' => [], 'fields' => ['oro_product.new_arrivals_max_items']],
        ];
        $ds = $this->createDatasource();

        $result = $this->filter->apply($ds, ['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'Promotions']);

        self::assertTrue($result);
        $where = $this->whereString($ds);
        self::assertStringContainsStringIgnoringCase(' IN (', $where);
        self::assertStringContainsString('oro_product.new_arrivals_max_items', $where);
        self::assertStringNotContainsString('a.objectClass IN', $where);
    }

    public function testMatchesFieldsOfAnAuditTypeScopedByClass(): void
    {
        $this->matchingAuditTypeGroups = [
            ['classes' => ['Oro\Bundle\CommerceMenuBundle\WebsiteStorefrontMenu'], 'fields' => ['Contact Us']],
        ];
        $ds = $this->createDatasource();

        $result = $this->filter->apply($ds, ['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'Contact']);

        self::assertTrue($result);
        $where = $this->whereString($ds);
        self::assertStringContainsString(
            'a.objectClass IN (Oro\Bundle\CommerceMenuBundle\WebsiteStorefrontMenu)',
            $where
        );
        self::assertStringContainsString('.field IN (Contact Us)', $where);
    }

    public function testKeepsAnUnscopedGroupWhenAScopedOneMatchesAsWell(): void
    {
        $this->matchingAuditTypeGroups = [
            ['classes' => [], 'fields' => ['oro_product.new_arrivals_max_items']],
            ['classes' => ['Oro\Bundle\CommerceMenuBundle\WebsiteStorefrontMenu'], 'fields' => ['Contact Us']],
        ];
        $ds = $this->createDatasource();

        $result = $this->filter->apply($ds, ['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'Contact']);

        self::assertTrue($result);
        $where = $this->whereString($ds);
        self::assertStringContainsString('.field IN (oro_product.new_arrivals_max_items)', $where);
        self::assertStringContainsString(
            'a.objectClass IN (Oro\Bundle\CommerceMenuBundle\WebsiteStorefrontMenu)',
            $where
        );
        self::assertStringContainsString('.field IN (Contact Us)', $where);
        self::assertDoesNotMatchRegularExpression(
            '/objectClass IN \([^)]*\) AND [^)]*oro_product\.new_arrivals_max_items/',
            $where
        );
    }

    public function testMatchesEntityFieldsByLabelScopedByClass(): void
    {
        $this->matchingEntityFields = [
            'classes' => ['Oro\Bundle\UserBundle\Entity\User'],
            'fields' => ['email'],
        ];
        $ds = $this->createDatasource();

        $result = $this->filter->apply($ds, ['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'Primary Email']);

        self::assertTrue($result);
        $where = $this->whereString($ds);
        self::assertStringContainsString('a.objectClass IN (Oro\Bundle\UserBundle\Entity\User)', $where);
        self::assertStringContainsString('.field IN (email)', $where);
    }

    public function testDoesNotAddNameConditionsWhenNothingMatches(): void
    {
        $ds = $this->createDatasource();

        $result = $this->filter->apply($ds, ['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'nothing']);

        self::assertTrue($result);
        self::assertStringNotContainsStringIgnoringCase(' IN (', $this->whereString($ds));
    }

    private function createDatasource(): OrmFilterDatasourceAdapter
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::any())
            ->method('getExpressionBuilder')
            ->willReturn(new Expr());
        $connection = $this->createMock(Connection::class);
        $em->expects(self::any())
            ->method('getConnection')
            ->willReturn($connection);
        $connection->expects(self::any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform());

        $qb = new QueryBuilder($em);
        $qb->select('a')->from(Audit::class, 'a');

        return new OrmFilterDatasourceAdapter($qb);
    }

    private function whereString(OrmFilterDatasourceAdapter $ds): string
    {
        $qb = $ds->getQueryBuilder();
        $where = $qb->getDQLPart('where');
        if (!$where) {
            return '';
        }

        $parameters = [];
        foreach ($qb->getParameters() as $parameter) {
            $value = $parameter->getValue();
            $parameters[':' . $parameter->getName()] = \is_array($value) ? implode(',', $value) : (string)$value;
        }

        return str_replace(array_keys($parameters), array_values($parameters), (string)$where);
    }
}
