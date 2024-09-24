<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\FieldDqlExpressionProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;

class FieldDqlExpressionProviderTest extends OrmRelatedTestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FieldDqlExpressionProvider */
    private $fieldDqlExpressionProvider;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->configManager = $this->createMock(ConfigManager::class);

        $this->fieldDqlExpressionProvider = new FieldDqlExpressionProvider($this->configManager);
    }

    public function testGetFieldDqlExpressionForComputedField(): void
    {
        $qb = new QueryBuilder($this->em);

        $this->configManager->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->fieldDqlExpressionProvider->getFieldDqlExpression($qb, 'computed'));
    }

    public function testGetFieldDqlExpressionWhenEntityClassNotFound(): void
    {
        $qb = new QueryBuilder($this->em);
        $qb->from(Group::class, 'e')
            ->select('e');

        $this->configManager->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->fieldDqlExpressionProvider->getFieldDqlExpression($qb, 'a.id'));
    }

    public function testGetFieldDqlExpressionForNotConfigurableField(): void
    {
        $qb = new QueryBuilder($this->em);
        $qb->from(Group::class, 'e')
            ->select('e');

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(Group::class, 'name')
            ->willReturn(false);
        $this->configManager->expects(self::never())
            ->method('getId');

        self::assertNull($this->fieldDqlExpressionProvider->getFieldDqlExpression($qb, 'e.name'));
    }

    public function testGetFieldDqlExpressionForNotEnum(): void
    {
        $qb = new QueryBuilder($this->em);
        $qb->from(Group::class, 'e')
            ->select('e');

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(Group::class, 'name')
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getId')
            ->with('extend', Group::class, 'name')
            ->willReturn(new FieldConfigId('extend', Group::class, 'name', 'integer'));

        self::assertNull($this->fieldDqlExpressionProvider->getFieldDqlExpression($qb, 'e.name'));
    }

    public function testGetFieldDqlExpressionForEnum(): void
    {
        $qb = new QueryBuilder($this->em);
        $qb->from(Group::class, 'e')
            ->select('e');

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(Group::class, 'name')
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getId')
            ->with('extend', Group::class, 'name')
            ->willReturn(new FieldConfigId('extend', Group::class, 'name', 'enum'));

        self::assertEquals(
            "JSON_EXTRACT(e.serialized_data, 'name')",
            $this->fieldDqlExpressionProvider->getFieldDqlExpression($qb, 'e.name')
        );
    }

    public function testGetFieldDqlExpressionForMultiEnum(): void
    {
        $qb = new QueryBuilder($this->em);
        $qb->from(Group::class, 'e')
            ->select('e');

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(Group::class, 'name')
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getId')
            ->with('extend', Group::class, 'name')
            ->willReturn(new FieldConfigId('extend', Group::class, 'name', 'multiEnum'));

        self::assertEquals(
            sprintf(
                "JSONB_ARRAY_CONTAINS_JSON(e.serialized_data, 'name', CONCAT('\"', {entity:%s}.id, '\"')) = true",
                EnumOption::class
            ),
            $this->fieldDqlExpressionProvider->getFieldDqlExpression($qb, 'e.name')
        );
    }
}
