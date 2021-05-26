<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Helper;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\LocaleBundle\Helper\LocalizationQueryTrait;

class LocalizationQueryTraitTest extends \PHPUnit\Framework\TestCase
{
    use LocalizationQueryTrait;

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $queryBuilder;

    protected function setUp(): void
    {
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
    }

    // joinDefaultLocalizedValue call tested implicitly
    public function testJoinDefaultLocalizedValue()
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

    public function testLeftJoinDefaultLocalizedValue()
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
