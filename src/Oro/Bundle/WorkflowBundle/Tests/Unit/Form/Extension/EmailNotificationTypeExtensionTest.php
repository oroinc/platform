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
    private $listener;

    /** @var EmailNotificationTypeExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->listener = $this->createMock(EmailNotificationTypeListener::class);

        $this->extension = new EmailNotificationTypeExtension($this->listener);

        parent::setUp();
    }

    public function testGetExtendedTypes()
    {
        $this->assertEquals([EmailNotificationType::class], EmailNotificationTypeExtension::getExtendedTypes());
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(2))
            ->method('addEventListener')
            ->withConsecutive(
                [FormEvents::POST_SET_DATA, [$this->listener, 'onPostSetData']],
                [FormEvents::PRE_SUBMIT, [$this->listener, 'onPreSubmit']]
            );

        $this->extension->buildForm($builder, []);
    }

    public function testFinishView()
    {
        $childView1 = $this->getFormView('eventName');
        $childView2 = $this->getFormView('unsupported');
        $childView3 = $this->getFormView('workflow_definition');

        $formView = new FormView();
        $formView->vars['listenChangeElements'] = ['#entity_name'];
        $formView->children = [
            $childView1->vars['name'] => $childView1,
            $childView2->vars['name'] => $childView2,
            $childView3->vars['name'] => $childView3
        ];

        $form = $this->createMock(FormInterface::class);

        $this->extension->finishView($formView, $form, []);

        $this->assertArrayHasKey('listenChangeElements', $formView->vars);
        $this->assertEquals(
            ['#entity_name', '#eventName_id', '#workflow_definition_id'],
            $formView->vars['listenChangeElements']
        );
    }

    private function getFormView(string $name): FormView
    {
        $view = new FormView();
        $view->vars['id'] = $name . '_id';
        $view->vars['name'] = $name;

        return $view;
    }
}
