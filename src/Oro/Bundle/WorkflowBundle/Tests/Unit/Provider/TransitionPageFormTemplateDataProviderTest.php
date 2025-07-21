<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Oro\Bundle\WorkflowBundle\Provider\TransitionPageFormTemplateDataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

class TransitionPageFormTemplateDataProviderTest extends TestCase
{
    private TransitionPageFormTemplateDataProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new TransitionPageFormTemplateDataProvider();
    }

    public function testGetData(): void
    {
        $formView = $this->createMock(FormView::class);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $entity = new \stdClass();

        $request = $this->createMock(Request::class);

        $this->assertSame(
            ['entity' => $entity, 'form' => $formView],
            $this->provider->getData($entity, $form, $request)
        );
    }
}
