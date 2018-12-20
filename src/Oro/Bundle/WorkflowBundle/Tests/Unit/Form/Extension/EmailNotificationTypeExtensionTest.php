<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\NotificationBundle\Form\Type\EmailNotificationType;
use Oro\Bundle\WorkflowBundle\Form\EventListener\EmailNotificationTypeListener;
use Oro\Bundle\WorkflowBundle\Form\Extension\EmailNotificationTypeExtension;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class EmailNotificationTypeExtensionTest extends FormIntegrationTestCase
{
    /** @var EmailNotificationTypeListener|\PHPUnit\Framework\MockObject\MockObject */
    protected $listener;

    /** @var EmailNotificationTypeExtension */
    protected $extension;

    protected function setUp()
    {
        $this->listener = $this->createMock(EmailNotificationTypeListener::class);

        $this->extension = new EmailNotificationTypeExtension($this->listener);

        parent::setUp();
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(EmailNotificationType::class, $this->extension->getExtendedType());
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->at(0))
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$this->listener, 'onPostSetData']);
        $builder->expects($this->at(1))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SUBMIT, [$this->listener, 'onPreSubmit']);

        $this->extension->buildForm($builder, []);
    }

    public function testFinishView()
    {
        $childView1 = $this->getFormView('event');
        $childView2 = $this->getFormView('unsupported');
        $childView3 = $this->getFormView('workflow_definition');

        $formView = new FormView();
        $formView->vars['listenChangeElements'] = ['#entity_name_id'];
        $formView->children = [
            $childView1->vars['name'] => $childView1,
            $childView2->vars['name'] => $childView2,
            $childView3->vars['name'] => $childView3
        ];

        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);

        $this->extension->finishView($formView, $form, []);

        $this->assertArrayHasKey('listenChangeElements', $formView->vars);
        $this->assertEquals(
            ['#entity_name_id', '#event_id', '#workflow_definition_id'],
            $formView->vars['listenChangeElements']
        );
    }

    /**
     * @param string $name
     * @return FormView
     */
    protected function getFormView($name)
    {
        $view = new FormView();
        $view->vars['id'] = $name . '_id';
        $view->vars['name'] = $name;

        return $view;
    }
}
