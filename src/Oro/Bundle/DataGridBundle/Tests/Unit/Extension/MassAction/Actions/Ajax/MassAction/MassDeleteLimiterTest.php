<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction\Actions\Ajax\MassAction;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\MassDelete\MassDeleteLimiter;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\MassDelete\MassDeleteLimitResult;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class MassDeleteLimiterTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $helper;

    /** @var MassDeleteLimiter */
    private $limiter;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(AclHelper::class);

        $this->limiter = new MassDeleteLimiter($this->helper);
    }

    /**
     * @dataProvider getLimitationCodeDataProvider
     */
    public function testGetLimitationCode(MassDeleteLimitResult $limitResult, int $result)
    {
        $this->assertEquals($result, $this->limiter->getLimitationCode($limitResult));
    }

    /**
     * @dataProvider getLimitQueryDataProvider
     */
    public function testLimitQuery(
        MassDeleteLimitResult $limitResult,
        bool $accessRestriction = false,
        bool $maxLimitRestriction = false
    ) {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $args = $this->createMock(MassActionHandlerArgs::class);
        $args->expects($this->once())
            ->method('getResults')
            ->willReturn(new IterableResult($queryBuilder));

        if ($accessRestriction) {
            $this->helper->expects($this->once())
                ->method('apply')
                ->with($queryBuilder, 'DELETE');
        }

        if ($maxLimitRestriction) {
            $queryBuilder->expects($this->once())
                ->method('setMaxResults')
                ->with($limitResult->getMaxLimit());
        }

        $this->limiter->limitQuery($limitResult, $args);
    }

    public function getLimitQueryDataProvider(): array
    {
        return [
            'no limits' => [
                $this->getMassDeleteResult(MassDeleteLimiter::NO_LIMIT)
            ],
            'limit by access' => [
                $this->getMassDeleteResult(MassDeleteLimiter::LIMIT_ACCESS),
                true
            ],
            'limit by max records' => [
                $this->getMassDeleteResult(MassDeleteLimiter::LIMIT_MAX_RECORDS),
                false,
                true
            ],
            'limit by access and max records' => [
                $this->getMassDeleteResult(MassDeleteLimiter::LIMIT_ACCESS_MAX_RECORDS),
                true,
                true
            ],
        ];
    }

    public function getLimitationCodeDataProvider(): array
    {
        return [
            'no limits code' => [
                $this->getMassDeleteResult(MassDeleteLimiter::NO_LIMIT),
                MassDeleteLimiter::NO_LIMIT
            ],
            'limit by access code' => [
                $this->getMassDeleteResult(MassDeleteLimiter::LIMIT_ACCESS),
                MassDeleteLimiter::LIMIT_ACCESS
            ],
            'limit by max records code' => [
                $this->getMassDeleteResult(MassDeleteLimiter::LIMIT_MAX_RECORDS),
                MassDeleteLimiter::LIMIT_MAX_RECORDS
            ],
            'limit by access and max records code' => [
                $this->getMassDeleteResult(MassDeleteLimiter::LIMIT_ACCESS_MAX_RECORDS),
                MassDeleteLimiter::LIMIT_ACCESS_MAX_RECORDS
            ],
        ];
    }

    private function getMassDeleteResult($code)
    {
        switch ($code) {
            case MassDeleteLimiter::LIMIT_ACCESS:
                $result = new MassDeleteLimitResult(100, 50, 1000);
                break;
            case MassDeleteLimiter::LIMIT_MAX_RECORDS:
                $result = new MassDeleteLimitResult(2000, 2000, 1000);
                break;
            case MassDeleteLimiter::LIMIT_ACCESS_MAX_RECORDS:
                $result = new MassDeleteLimitResult(2000, 1500, 1000);
                break;
            default:
                $result = new MassDeleteLimitResult(100, 100, 1000);
                break;
        }

        return $result;
    }
}
