<?php

namespace Oro\Bundle\WorkflowBundle\Form\Extension;

use Oro\Bundle\NotificationBundle\Form\Type\EmailNotificationType;
use Oro\Bundle\WorkflowBundle\Form\EventListener\EmailNotificationTypeListener;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class EmailNotificationTypeExtension extends AbstractTypeExtension
{
    /** @var EmailNotificationTypeListener */
    protected $listener;

    /**
     * @param EmailNotificationTypeListener $listener
     */
    public function __construct(EmailNotificationTypeListener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return EmailNotificationType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this->listener, 'onPostSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this->listener, 'onPreSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $elements = array_filter(
            array_map(
                function (FormView $view) {
                    if (in_array($view->vars['name'], ['event', 'workflow_definition'], true)) {
                        return '#' . $view->vars['id'];
                    }

                    return null;
                },
                array_values($view->children)
            )
        );

        $view->vars['listenChangeElements'] = array_merge($view->vars['listenChangeElements'], $elements);
    }
}
