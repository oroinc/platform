<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use PHPUnit\Framework\TestCase;

class GetListContextTest extends TestCase
{
    private GetListContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new GetListContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
    }

    private function assertInitializeCriteriaCallback(callable $initializeCriteriaCallback): void
    {
        $this->context->setInitializeCriteriaCallback($initializeCriteriaCallback);
        self::assertSame($initializeCriteriaCallback, $this->context->getInitializeCriteriaCallback());
        self::assertSame($initializeCriteriaCallback, $this->context->get('initializeCriteriaCallback'));
        $criteria = new Criteria();
        self::assertNull($criteria->getWhereExpression());
        call_user_func($this->context->getInitializeCriteriaCallback(), $criteria);
        self::assertNotNull($criteria->getWhereExpression());
    }

    public function initializeCriteria(Criteria $criteria): void
    {
        $criteria->andWhere(Criteria::expr()->eq('id', 1));
    }

    public function testInitializeCriteriaCallback(): void
    {
        self::assertNull($this->context->getInitializeCriteriaCallback());

        $this->assertInitializeCriteriaCallback(
            function (Criteria $criteria): void {
                $criteria->andWhere(Criteria::expr()->eq('id', 1));
            }
        );

        $this->assertInitializeCriteriaCallback(
            new class() {
                public function __invoke(Criteria $criteria): void
                {
                    $criteria->andWhere(Criteria::expr()->eq('id', 1));
                }
            }
        );

        $this->assertInitializeCriteriaCallback([$this, 'initializeCriteria']);
    }
}
