<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Helper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityListBundle\Helper\ActivityInheritanceTargetsHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\SubQueryLimitHelper;

class ActivityInheritanceTargetsHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var SubQueryLimitHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $limitHelper;

    /** @var ActivityInheritanceTargetsHelper */
    private $activityInheritanceTargetsHelper;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->limitHelper = $this->createMock(SubQueryLimitHelper::class);

        $this->activityInheritanceTargetsHelper = new ActivityInheritanceTargetsHelper(
            $this->configManager,
            $this->doctrine,
            $this->limitHelper
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
            ->method('hasConfigEntityModel')
            ->with('test')
            ->willReturn(null);
        $result = $this->activityInheritanceTargetsHelper->hasInheritances('test');
        $this->assertFalse($result);
    }

    public function testHasInheritancesEmptyValues()
    {
        $config = $this->createMock(Config::class);
        $config->expects($this->once())
            ->method('getValues')
            ->willReturn([]);

        $this->configManager->expects($this->once())
            ->method('hasConfigEntityModel')
            ->with('test')
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('activity', 'test')
            ->willReturn($config);

        $result = $this->activityInheritanceTargetsHelper->hasInheritances('test');
        $this->assertFalse($result);
    }

    public function testHasInheritancesNullValues()
    {
        $config = $this->createMock(Config::class);
        $config->expects($this->once())
            ->method('getValues')
            ->willReturn(null);

        $this->configManager->expects($this->once())
            ->method('hasConfigEntityModel')
            ->with('test')
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('activity', 'test')
            ->willReturn($config);

        $result = $this->activityInheritanceTargetsHelper->hasInheritances('test');
        $this->assertFalse($result);
    }

    public function testHasInheritancesNotConfigured()
    {
        $config = $this->createMock(Config::class);
        $config->expects($this->once())
            ->method('getValues')
            ->willReturn(['some']);

        $this->configManager->expects($this->once())
            ->method('hasConfigEntityModel')
            ->with('test')
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('activity', 'test')
            ->willReturn($config);

        $result = $this->activityInheritanceTargetsHelper->hasInheritances('test');
        $this->assertFalse($result);
    }

    public function testHasInheritancesConfigured()
    {
        $config = $this->createMock(Config::class);
        $config->expects($this->once())
            ->method('getValues')
            ->willReturn(['inheritance_targets' => ['test']]);

        $this->configManager->expects($this->once())
            ->method('hasConfigEntityModel')
            ->with('test')
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('activity', 'test')
            ->willReturn($config);

        $result = $this->activityInheritanceTargetsHelper->hasInheritances('test');
        $this->assertTrue($result);
    }

    public function testApplyInheritanceActivity()
    {
        $mainQb = $this->prepareMock();

        $this->limitHelper->expects($this->once())
            ->method('setLimit');

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
            ':entityId'
        );

        $expectedDQL = 'SELECT activity.id, activity.updatedAt '
            . 'FROM ActivityList activity '
            . 'LEFT JOIN activity.contact_e8d5b2ba ta_0 '
            . 'WHERE ta_0.id IN(SELECT inherit_0.id'
            . ' FROM Acme\Bundle\AcmeBundle\Entity\Contact inherit_0'
            . ' INNER JOIN inherit_0.accounts t_0_0 WHERE t_0_0.id = :entityId)';

        $this->assertSame($expectedDQL, $mainQb->getDQL());
    }

    private function prepareMock(): QueryBuilder
    {
        $em = $this->createMock(EntityManager::class);
        $expr = new Expr();
        $em->expects($this->any())
            ->method('getExpressionBuilder')
            ->willReturn($expr);

        $mainQb = new QueryBuilder($em);
        $inheritedQb = clone $mainQb;

        $mainQb->select('activity.id, activity.updatedAt');
        $mainQb->from('ActivityList', 'activity');

        $em->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($inheritedQb);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        return $mainQb;
    }
}
