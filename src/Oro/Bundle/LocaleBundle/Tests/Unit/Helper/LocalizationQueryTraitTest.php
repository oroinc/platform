<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Helper;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\LocaleBundle\Helper\LocalizationQueryTrait;

class LocalizationQueryTraitTest extends \PHPUnit\Framework\TestCase
{
    use LocalizationQueryTrait;

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $queryBuilder;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    // joinDefaultLocalizedValue call tested implicitly
    public function testJoinDefaultLocalizedValue()
    {
        $this->queryBuilder->expects($this->at(0))
            ->method('addSelect')
            ->with('joinAlias.string as fieldAlias')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->at(1))
            ->method('innerJoin')
            ->with('join', 'joinAlias', Join::WITH, 'joinAlias.localization IS NULL')
            ->willReturn($this->queryBuilder);

        $this->assertSame(
            $this->queryBuilder,
            $this->joinDefaultLocalizedValue($this->queryBuilder, 'join', 'joinAlias', 'fieldAlias')
        );
    }

    public function testLeftJoinDefaultLocalizedValue()
    {
        $this->queryBuilder->expects($this->at(0))
            ->method('addSelect')
            ->with('joinAlias.string as fieldAlias')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->at(1))
            ->method('leftJoin')
            ->with('join', 'joinAlias', Join::WITH, 'joinAlias.localization IS NULL')
            ->willReturn($this->queryBuilder);

        $this->assertSame(
            $this->queryBuilder,
            $this->joinDefaultLocalizedValue($this->queryBuilder, 'join', 'joinAlias', 'fieldAlias', 'leftJoin')
        );
    }
}
