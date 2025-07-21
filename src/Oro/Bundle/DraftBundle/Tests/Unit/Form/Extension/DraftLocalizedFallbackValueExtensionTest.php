<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\DraftBundle\Form\Extension\DraftLocalizedFallbackValueExtension;
use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class DraftLocalizedFallbackValueExtensionTest extends TestCase
{
    private DraftLocalizedFallbackValueExtension $extension;
    private DraftHelper&MockObject $draftHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->draftHelper = $this->createMock(DraftHelper::class);

        $this->extension = new DraftLocalizedFallbackValueExtension($this->draftHelper);
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::PRE_SUBMIT, [$this->extension, 'preSubmit'])
            ->willReturnSelf();

        $this->extension->buildForm($builder, []);
    }

    public function testOnPreSetData(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn(new DraftableEntityStub());
        $this->draftHelper->expects($this->once())
            ->method('isSaveAsDraftAction')
            ->willReturn(true);

        $event = new FormEvent($form, []);
        $event->setData(['ids' => [1,2]]);
        $this->extension->preSubmit($event);

        $this->assertEmpty($event->getData());
    }
}
