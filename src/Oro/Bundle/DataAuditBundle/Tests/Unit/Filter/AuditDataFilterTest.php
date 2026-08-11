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
use Oro\Bundle\DataAuditBundle\Provider\ConfigAuditFieldLabelProvider;
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
    private array $matchingKeys = [];
    private array $matchingEntityFields = ['classes' => [], 'fields' => []];
    private AuditDataFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $configFieldLabelProvider = $this->createMock(ConfigAuditFieldLabelProvider::class);
        $configFieldLabelProvider->expects(self::any())
            ->method('getMatchingFieldKeys')
            ->willReturnCallback(fn (): array => $this->matchingKeys);

        $entityFieldSearchProvider = $this->createMock(EntityAuditFieldSearchProvider::class);
        $entityFieldSearchProvider->expects(self::any())
            ->method('getMatchingFields')
            ->willReturnCallback(fn (): array => $this->matchingEntityFields);

        $this->filter = new AuditDataFilter(
            $this->createMock(FormFactoryInterface::class),
            new FilterUtility(),
            $configFieldLabelProvider,
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
        // The term is wrapped once (by StringFilter::parseValue), not double-wrapped.
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

    public function testMatchesConfigurationKeysResolvedFromTheBreadcrumb(): void
    {
        // A setting whose displayed breadcrumb contains the term is resolved to its stored key and
        // matched, even though the stored "field" is the key, not the translated path.
        $this->matchingKeys = ['oro_product.new_arrivals_max_items'];
        $ds = $this->createDatasource();

        $result = $this->filter->apply($ds, ['type' => TextFilterType::TYPE_CONTAINS, 'value' => 'Promotions']);

        self::assertTrue($result);
        $where = $this->whereString($ds);
        self::assertStringContainsStringIgnoringCase(' IN (', $where);
        self::assertStringContainsString('oro_product.new_arrivals_max_items', $where);
    }

    public function testMatchesEntityFieldsByLabelScopedByClass(): void
    {
        // "Primary Email" is the label of User::email; the audit stores the field name, so the matching
        // classes and field names are added as two scoped sets.
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
