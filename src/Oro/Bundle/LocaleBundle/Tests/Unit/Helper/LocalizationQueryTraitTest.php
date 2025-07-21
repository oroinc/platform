<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Helper;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\LocaleBundle\Helper\LocalizationQueryTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocalizationQueryTraitTest extends TestCase
{
    use LocalizationQueryTrait;

    private QueryBuilder&MockObject $queryBuilder;

    #[\Override]
    protected function setUp(): void
    {
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
    }

    // joinDefaultLocalizedValue call tested implicitly
    public function testJoinDefaultLocalizedValue(): void
    {
        $this->queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('joinAlias.string as fieldAlias')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('innerJoin')
            ->with('join', 'joinAlias', Join::WITH, 'joinAlias.localization IS NULL')
            ->willReturnSelf();

        $this->assertSame(
            $this->queryBuilder,
            $this->joinDefaultLocalizedValue($this->queryBuilder, 'join', 'joinAlias', 'fieldAlias')
        );
    }

    public function testLeftJoinDefaultLocalizedValue(): void
    {
        $this->queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('joinAlias.string as fieldAlias')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('join', 'joinAlias', Join::WITH, 'joinAlias.localization IS NULL')
            ->willReturnSelf();

        $this->assertSame(
            $this->queryBuilder,
            $this->joinDefaultLocalizedValue($this->queryBuilder, 'join', 'joinAlias', 'fieldAlias', 'leftJoin')
        );
    }
}
