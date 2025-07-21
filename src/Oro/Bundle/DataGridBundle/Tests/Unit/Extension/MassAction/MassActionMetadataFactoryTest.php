<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionMetadataFactory;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionMetadataFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MassActionMetadataFactoryTest extends TestCase
{
    private ActionMetadataFactory&MockObject $actionMetadataFactory;
    private MassActionMetadataFactory $massActionMetadataFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionMetadataFactory = $this->createMock(ActionMetadataFactory::class);

        $this->massActionMetadataFactory = new MassActionMetadataFactory($this->actionMetadataFactory);
    }

    public function testCreateActionMetadata(): void
    {
        $action = $this->createMock(MassActionInterface::class);
        $actionMetadata = ['label' => 'label1'];

        $this->actionMetadataFactory->expects(self::once())
            ->method('createActionMetadata')
            ->with(self::identicalTo($action))
            ->willReturn($actionMetadata);

        self::assertEquals(
            $actionMetadata,
            $this->massActionMetadataFactory->createActionMetadata($action)
        );
    }
}
