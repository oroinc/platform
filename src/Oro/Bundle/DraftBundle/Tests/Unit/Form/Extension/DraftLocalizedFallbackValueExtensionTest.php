<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\DraftBundle\Form\Extension\DraftLocalizedFallbackValueExtension;
use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class DraftLocalizedFallbackValueExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var DraftLocalizedFallbackValueExtension */
    private $extension;

    /** @var DraftHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $draftHelper;

    protected function setUp(): void
    {
        $this->draftHelper = $this->createMock(DraftHelper::class);

        $this->extension = new DraftLocalizedFallbackValueExtension($this->draftHelper);
    }

    public function testBuildForm(): void
    {
        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder
            ->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::PRE_SUBMIT, [$this->extension, 'preSubmit'])
            ->willReturnSelf();

        $this->extension->buildForm($builder, []);
    }

    public function testOnPreSetData(): void
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getParent')
            ->willReturn(null);
        $form
            ->expects($this->once())
            ->method('getData')
            ->willReturn(new DraftableEntityStub());
        $this->draftHelper
            ->expects($this->once())
            ->method('isSaveAsDraftAction')
            ->willReturn(true);

        $event = new FormEvent($form, []);
        $event->setData(['ids' => [1,2]]);
        $this->extension->preSubmit($event);

        $this->assertEmpty($event->getData());
    }
}
