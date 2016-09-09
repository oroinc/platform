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

    public function testApplyInheritanceActivity()
    {
        $mainQb = $this->prepareMock();

        $this->activityInheritanceTargetsHelper->applyInheritanceActivity(
            $mainQb,
            [
                'targetClass' => 'Acme\Bundle\AcmeBundle\Entity\Contact',
                'targetClassAlias' => 'contact_e8d5b2ba',
                'path' => [
                    'accounts'
                ]
            ],
            0,
            ':entityId',
            false
        );

        $expectedDQL = 'SELECT activity.id, activity.updatedAt '
            . 'FROM ActivityList activity '
            . 'LEFT JOIN activity.contact_e8d5b2ba ta_0 '
            . 'WHERE ta_0.id IN(SELECT inherit_0.id'
            . ' FROM Acme\Bundle\AcmeBundle\Entity\Contact inherit_0'
            . ' INNER JOIN inherit_0.accounts t_0_0 WHERE t_0_0.id = :entityId)';

        $this->assertSame($expectedDQL, $mainQb->getDQL());
    }

    public function testApplyInheritanceActivityHeadOnly()
    {
        $mainQb = $this->prepareMock();

        $this->activityInheritanceTargetsHelper->applyInheritanceActivity(
            $mainQb,
            [
                'targetClass' => 'Acme\Bundle\AcmeBundle\Entity\Contact',
                'targetClassAlias' => 'contact_e8d5b2ba',
                'path' => [
                    'accounts'
                ]
            ],
            0,
            ':entityId',
            true
        );

        $expectedDQL = 'SELECT activity.id, activity.updatedAt '
            . 'FROM ActivityList activity '
            . 'LEFT JOIN activity.contact_e8d5b2ba ta_0 '
            . 'WHERE ta_0.id IN(SELECT inherit_0.id'
            . ' FROM Acme\Bundle\AcmeBundle\Entity\Contact inherit_0'
            . ' INNER JOIN inherit_0.accounts t_0_0 WHERE t_0_0.id = :entityId) AND activity.head = true';

        $this->assertSame($expectedDQL, $mainQb->getDQL());
    }

    /**
     * @return QueryBuilder
     */
    protected function prepareMock()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $expr = new Expr();
        $em->expects($this->any())->method('getExpressionBuilder')->willReturn($expr);

        $mainQb = new QueryBuilder($em);
        $inheritedQb = clone $mainQb;

        $mainQb->select('activity.id, activity.updatedAt');
        $mainQb->from('ActivityList', 'activity');

        $em->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($inheritedQb);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        return $mainQb;
    }
}
