<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionMetadataFactory;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ActionMetadataFactoryTest extends TestCase
{
    private TranslatorInterface&MockObject $translator;
    private ActionMetadataFactory $actionMetadataFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->actionMetadataFactory = new ActionMetadataFactory($this->translator);
    }

    public function testCreateActionMetadataEmptyOptions(): void
    {
        $action = $this->createMock(ActionInterface::class);
        $actionOptions = ActionConfiguration::create([]);

        $action->expects(self::once())
            ->method('getOptions')
            ->willReturn($actionOptions);

        $this->translator->expects(self::never())
            ->method('trans');

        self::assertEquals(
            ['label' => null],
            $this->actionMetadataFactory->createActionMetadata($action)
        );
    }

    public function testCreateActionMetadataWithAclOption(): void
    {
        $action = $this->createMock(ActionInterface::class);
        $actionOptions = ActionConfiguration::create(['acl_resource' => 'acl_resource1']);

        $action->expects(self::once())
            ->method('getOptions')
            ->willReturn($actionOptions);

        $this->translator->expects(self::never())
            ->method('trans');

        self::assertEquals(
            ['label' => null],
            $this->actionMetadataFactory->createActionMetadata($action)
        );
    }

    public function testCreateActionMetadataWithLabel(): void
    {
        $action = $this->createMock(ActionInterface::class);
        $actionOptions = ActionConfiguration::create(['label' => 'label1']);

        $action->expects(self::once())
            ->method('getOptions')
            ->willReturn($actionOptions);

        $this->translator->expects(self::once())
            ->method('trans')
            ->with('label1')
            ->willReturn('translated_label1');

        self::assertEquals(
            ['label' => 'translated_label1'],
            $this->actionMetadataFactory->createActionMetadata($action)
        );
    }

    public function testCreateActionMetadataWithAlreadyTranslatedLabel(): void
    {
        $action = $this->createMock(ActionInterface::class);
        $actionOptions = ActionConfiguration::create(['label' => 'label1', 'translatable' => false]);

        $action->expects(self::once())
            ->method('getOptions')
            ->willReturn($actionOptions);

        $this->translator->expects(self::never())
            ->method('trans');

        self::assertEquals(
            ['label' => 'label1'],
            $this->actionMetadataFactory->createActionMetadata($action)
        );
    }
}
