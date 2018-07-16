<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionMetadataFactory;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionMetadataFactory;

class MassActionMetadataFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $actionMetadataFactory;

    /** @var MassActionMetadataFactory */
    protected $massActionMetadataFactory;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->actionMetadataFactory = $this->createMock(ActionMetadataFactory::class);

        $this->massActionMetadataFactory = new MassActionMetadataFactory($this->actionMetadataFactory);
    }

    public function testCreateActionMetadata()
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
