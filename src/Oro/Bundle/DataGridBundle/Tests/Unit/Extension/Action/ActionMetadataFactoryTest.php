<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionMetadataFactory;
use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ActionMetadataFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ActionMetadataFactory */
    private $actionMetadataFactory;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->actionMetadataFactory = new ActionMetadataFactory($this->translator);
    }

    public function testCreateActionMetadataEmptyOptions()
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

    public function testCreateActionMetadataWithAclOption()
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

    public function testCreateActionMetadataWithLabel()
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

    public function testCreateActionMetadataWithAlreadyTranslatedLabel()
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
