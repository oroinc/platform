<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Oro\Bundle\WorkflowBundle\Provider\TransitionPageFormTemplateDataProvider;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Stub\StubEntity;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

class TransitionPageFormTemplateDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TransitionPageFormTemplateDataProvider */
    protected $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->provider = new TransitionPageFormTemplateDataProvider();
    }

    public function testGetData()
    {
        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $form */
        $formView = $this->createMock(FormView::class);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('createView')->willReturn($formView);

        /** @var StubEntity|\PHPUnit\Framework\MockObject\MockObject $entity */
        $entity = $this->createMock(StubEntity::class);

        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(Request::class);

        $this->assertSame(
            ['entity' => $entity, 'form' => $formView],
            $this->provider->getData($entity, $form, $request)
        );
    }
}
