<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;
use Oro\Bundle\ActionBundle\Event\ActionGroupEventDispatcher;
use Oro\Bundle\ActionBundle\Exception\ActionGroupNotFoundException;
use Oro\Bundle\ActionBundle\Model\ActionGroup\ParametersResolver;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\ActionBundle\Model\Assembler\ActionGroupAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\ParameterAssembler;
use Oro\Component\Action\Action\ActionFactoryInterface;
use Oro\Component\ConfigExpression\ExpressionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Service\ServiceProviderInterface;

class ActionGroupRegistryTest extends TestCase
{
    private ConfigurationProviderInterface&MockObject $configurationProvider;
    private ServiceProviderInterface&MockObject $actionGroupServiceLocator;
    private ActionGroupRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->configurationProvider = $this->createMock(ConfigurationProviderInterface::class);
        $this->actionGroupServiceLocator = $this->createMock(ServiceProviderInterface::class);

        $assembler = new ActionGroupAssembler(
            $this->createMock(ActionFactoryInterface::class),
            $this->createMock(ExpressionFactory::class),
            new ParameterAssembler(),
            $this->createMock(ParametersResolver::class),
            $this->createMock(ActionGroupEventDispatcher::class),
            $this->actionGroupServiceLocator
        );

        $this->registry = new ActionGroupRegistry(
            $this->configurationProvider,
            $assembler
        );
    }

    /**
     * @dataProvider findByNameDataProvider
     */
    public function testFindByName(string $actionGroupName, ?string $expected): void
    {
        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->willReturn(
                [
                    'action_group1' => [
                        'label' => 'Label1'
                    ]
                ]
            );

        $actionGroup = $this->registry->findByName($actionGroupName);

        $this->assertEquals($expected, $actionGroup ? $actionGroup->getDefinition()->getName() : $actionGroup);
    }

    public function findByNameDataProvider(): array
    {
        return [
            'invalid actionGroup name' => [
                'actionGroupName' => 'test',
                'expected' => null
            ],
            'valid actionGroup name' => [
                'actionGroupName' => 'action_group1',
                'expected' => 'action_group1'
            ],
        ];
    }

    public function testGet(): void
    {
        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->willReturn(
                [
                    'action_group1' => [
                        'label' => 'Label1'
                    ]
                ]
            );

        $group = $this->registry->get('action_group1');

        $this->assertEquals('action_group1', $group->getDefinition()->getName());
    }

    public function testGetException(): void
    {
        $this->expectException(ActionGroupNotFoundException::class);
        $this->expectExceptionMessage('ActionGroup with name "not exists" not found');

        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([]);

        $this->registry->get('not exists');
    }
}
