<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction\Actions\Ajax\MassDelete;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datasource\Orm\DeletionIterableResult;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\MassDelete\MassDeleteLimiter;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\MassDelete\MassDeleteLimitResult;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class MassDeleteLimiterTest extends \PHPUnit_Framework_TestCase
{
    /** @var MassDeleteLimiter */
    protected $limiter;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AclHelper */
    protected $helper;

    public function setUp()
    {
        $this->helper = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->limiter = new MassDeleteLimiter($this->helper);
    }

    /**
     * @dataProvider getLimitationCodeDataProvider
     *
     * @param MassDeleteLimitResult $limitResult
     * @param int                   $result
     */
    public function testGetLimitationCode(MassDeleteLimitResult $limitResult, $result)
    {
        $this->assertEquals($result, $this->limiter->getLimitationCode($limitResult));
    }

    /**
     * @dataProvider getLimitQueryDataProvider
     *
     * @param MassDeleteLimitResult $limitResult
     * @param bool                  $accessRestriction
     * @param bool                  $maxLimitRestriction
     *
     * @internal     param $MassDeleteLimitResult $
     */
    public function testLimitQuery(
        MassDeleteLimitResult $limitResult,
        $accessRestriction = false,
        $maxLimitRestriction = false
    ) {
        /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $queryBuilder */
        $queryBuilder = $this
            ->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var MassActionHandlerArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $args
            ->expects($this->once())
            ->method('getResults')
            ->willReturn(new DeletionIterableResult($queryBuilder));

        if ($accessRestriction) {
            $this->helper
                ->expects($this->once())
                ->method('apply')
                ->with($queryBuilder, 'DELETE');
        }

        if ($maxLimitRestriction) {
            $queryBuilder
                ->expects($this->once())
                ->method('setMaxResults')
                ->with($limitResult->getMaxLimit());
        }

        $this->limiter->limitQuery($limitResult, $args);
    }

    public function getLimitQueryDataProvider()
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

    public function getLimitationCodeDataProvider()
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

    protected function getMassDeleteResult($code)
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
