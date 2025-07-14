<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\IntegrationBundle\Datagrid\ActionConfiguration;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActionConfigurationTest extends TestCase
{
    private TypesRegistry&MockObject $typesRegistry;
    private ActionConfiguration $actionConfiguration;

    #[\Override]
    protected function setUp(): void
    {
        $this->typesRegistry = $this->createMock(TypesRegistry::class);

        $this->actionConfiguration = new ActionConfiguration($this->typesRegistry);
    }

    public function testShouldReturnConfigForEnabledChannel(): void
    {
        $record = new ResultRecord([
            'enabled' => 'enabled',
            'editMode' => Channel::EDIT_MODE_ALLOW
        ]);

        $result = $this->actionConfiguration->getIsSyncAvailableCondition($record);

        $expected = [
            'activate' => false,
            'delete'   => true
        ];
        $this->assertEquals($expected, $result);
    }

    public function testShouldReturnConfigForDisabledChannel(): void
    {
        $record = new ResultRecord([
            'enabled' => 'disabled',
            'editMode' => Channel::EDIT_MODE_ALLOW
        ]);

        $result = $this->actionConfiguration->getIsSyncAvailableCondition($record);

        $expected = [
            'deactivate' => false,
            'schedule'   => false,
            'delete'     => true
        ];
        $this->assertEquals($expected, $result);
    }

    public function testShouldReturnConfigForEditModeDisallow(): void
    {
        $record = new ResultRecord([
            'enabled' => 'enabled',
            'editMode' => Channel::EDIT_MODE_DISALLOW
        ]);

        $result = $this->actionConfiguration->getIsSyncAvailableCondition($record);

        $this->assertEquals([
            'activate' => false,
            'delete' => false,
            'deactivate' => false,
        ], $result);
    }

    public function testDoesNotSupportSync(): void
    {
        $record = new ResultRecord([
            'enabled' => 'enabled',
            'editMode' => Channel::EDIT_MODE_DISALLOW
        ]);

        $this->typesRegistry->expects($this->once())
            ->method('supportsSync')
            ->willReturn(false);

        $result = $this->actionConfiguration->getIsSyncAvailableCondition($record);

        $this->assertEquals([
            'activate' => false,
            'delete' => false,
            'deactivate' => false,
            'schedule' => false
        ], $result);
    }
}
