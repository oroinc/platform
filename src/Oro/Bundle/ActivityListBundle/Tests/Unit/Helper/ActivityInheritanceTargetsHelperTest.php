<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Helper;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ActivityListBundle\Helper\ActivityInheritanceTargetsHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class ActivityInheritanceTargetsHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActivityInheritanceTargetsHelper */
    protected $activityInheritanceTargetsHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Registry */
    protected $registry;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()->getMock();

        $this->activityInheritanceTargetsHelper = new ActivityInheritanceTargetsHelper(
            $this->configManager,
            $this->registry
        );
    }

    public function testHasInheritancesDoNotHasConfig()
    {
        $result = $this->activityInheritanceTargetsHelper->hasInheritances('');
        $this->assertFalse($result);
    }

    public function testHasInheritancesNullModel()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfigEntityModel')->with('test')->willReturn(null);
        $result = $this->activityInheritanceTargetsHelper->hasInheritances('test');
        $this->assertFalse($result);
    }

    public function testHasInheritancesEmptyValues()
    {
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()->getMock();
        $config->expects($this->once())
            ->method('getValues')->willReturn([]);

        $this->configManager->expects($this->once())
            ->method('hasConfigEntityModel')->with('test')->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')->with('activity', 'test')->willReturn($config);

        $result = $this->activityInheritanceTargetsHelper->hasInheritances('test');
        $this->assertFalse($result);
    }

    public function testHasInheritancesNullValues()
    {
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()->getMock();
        $config->expects($this->once())
            ->method('getValues')->willReturn(null);

        $this->configManager->expects($this->once())
            ->method('hasConfigEntityModel')->with('test')->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')->with('activity', 'test')->willReturn($config);

        $result = $this->activityInheritanceTargetsHelper->hasInheritances('test');
        $this->assertFalse($result);
    }

    public function testHasInheritancesNotConfigured()
    {
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()->getMock();
        $config->expects($this->once())
            ->method('getValues')->willReturn(['some']);

        $this->configManager->expects($this->once())
            ->method('hasConfigEntityModel')->with('test')->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')->with('activity', 'test')->willReturn($config);

        $result = $this->activityInheritanceTargetsHelper->hasInheritances('test');
        $this->assertFalse($result);
    }

    public function testHasInheritancesConfigured()
    {
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()->getMock();
        $config->expects($this->once())
            ->method('getValues')->willReturn(['inheritance_targets' => ['test']]);

        $this->configManager->expects($this->once())
            ->method('hasConfigEntityModel')->with('test')->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')->with('activity', 'test')->willReturn($config);

        $result = $this->activityInheritanceTargetsHelper->hasInheritances('test');
        $this->assertTrue($result);
    }

    /**
     * @param array $activity
     * @param int $qbCalls
     * @param int $hasModelCalls
     * @param int $hasModelCalls
     *
     * @dataProvider getInheritanceDataProvider
     */
    public function testApplyInheritanceActivity($activity, $qbCalls, $hasModelCalls, $getConfigCalls)
    {
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()->getMock();
        $config->expects($this->exactly($getConfigCalls))
            ->method('getValues')->willReturn($activity);

        $this->configManager->expects($this->exactly($hasModelCalls))
            ->method('hasConfigEntityModel')->with('test')->willReturn(true);

        $this->configManager->expects($this->exactly($getConfigCalls))
            ->method('getEntityConfig')->with('activity', 'test')->willReturn($config);

        $expr = new Expr();
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $em->expects($this->any())->method('getExpressionBuilder')->willReturn($expr);

        $qb = new QueryBuilder($em);
        $qb->select('test')->from('test', 'ttt');
        $em->expects($this->exactly($qbCalls))->method('createQueryBuilder')->willReturn($qb);
        $this->registry->expects($this->exactly($qbCalls))
            ->method('getManagerForClass')->willReturn($em);

        $this->activityInheritanceTargetsHelper->applyInheritanceActivity($qb, 'test', 1);
    }

    /**
     * @return array
     */
    public function getInheritanceDataProvider()
    {
        return array(
            'no data'=> [
                'activity' => ['activities' => [], 'inheritance_targets' =>['test']],
                'qb_calls'            => 0,
                'has_model_calls'     => 1,
                'get_config_calls'    => 2,
            ],
            'one bad inheritance'=> [
                'activity' => ['activities' => [], 'inheritance_targets' =>[['target1' => 'test']]],
                'qb_calls'            => 0,
                'has_model_calls'     => 1,
                'get_config_calls'    => 2,
            ],
            'one inheritance'=> [
                'activity'            => [
                    'activities' => [],
                    'inheritance_targets' => [
                        ['target' => 'test', 'path' => ['account']]
                    ]
                ],
                'qb_calls'            => 1,
                'has_model_calls'     => 2,
                'get_config_calls'    => 3,
            ],
            'two inheritance'=> [
                'activity'            => [
                    'activities' => [],
                    'inheritance_targets' => [
                        ['target' => 'test', 'path' => ['account', 'contact']],
                        ['target' => 'test', 'path' => ['contact']]
                    ]
                ],
                'qb_calls'            => 2,
                'has_model_calls'     => 3,
                'get_config_calls'    => 4,
            ],
        );
    }
}
