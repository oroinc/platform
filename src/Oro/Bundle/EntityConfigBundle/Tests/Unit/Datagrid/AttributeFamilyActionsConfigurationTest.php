<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityConfigBundle\Datagrid\AttributeFamilyActionsConfiguration;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeFamilyManager;

class AttributeFamilyActionsConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttributeFamilyManager|\PHPUnit_Framework_MockObject_MockObject */
    private $familyManager;

    /** @var AttributeFamilyActionsConfiguration */
    private $attributeFamilyActionsConfiguration;

    protected function setUp()
    {
        $this->familyManager = $this->getMockBuilder(AttributeFamilyManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeFamilyActionsConfiguration = new attributeFamilyActionsConfiguration($this->familyManager);
    }

    public function testDeleteDisabled()
    {
        $this->familyManager->expects($this->once())
            ->method('isAttributeFamilyDeletable')
            ->with(777)
            ->willReturn(false);

        $record = new ResultRecord(['id' => 777]);

        $this->assertEquals(
            [
                'view' => true,
                'edit' => true,
                'delete' => false
            ],
            $this->attributeFamilyActionsConfiguration->configureActionsVisibility($record)
        );
    }

    public function testDeleteEnabled()
    {
        $this->familyManager->expects($this->once())
            ->method('isAttributeFamilyDeletable')
            ->with(777)
            ->willReturn(true);

        $record = new ResultRecord(['id' => 777]);

        $this->assertEquals(
            [
                'view' => true,
                'edit' => true,
                'delete' => true
            ],
            $this->attributeFamilyActionsConfiguration->configureActionsVisibility($record)
        );
    }
}
