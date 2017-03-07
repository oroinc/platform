<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Oro\Bundle\WorkflowBundle\Provider\TransitionPageFormTemplateDataProvider;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Stub\StubEntity;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

class TransitionPageFormTemplateDataProviderTest extends \PHPUnit_Framework_TestCase
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
        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $form */
        $formView = $this->createMock(FormView::class);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('createView')->willReturn($formView);

        /** @var StubEntity|\PHPUnit_Framework_MockObject_MockObject $entity */
        $entity = $this->createMock(StubEntity::class);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createMock(Request::class);

        $this->assertSame(
            ['entity' => $entity, 'form' => $formView],
            $this->provider->getData($entity, $form, $request)
        );
    }
}
