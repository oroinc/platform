<?php

namespace Oro\Bundle\NotificationBundle\Form\Handler;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Form\Handler\FormWithAjaxReloadHandler;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The handler for email notification form.
 */
class EmailNotificationHandler implements FormHandlerInterface
{
    public function __construct(private FormWithAjaxReloadHandler $formWithAjaxReloadHandler)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function process($data, FormInterface $form, Request $request)
    {
        if (!$data instanceof EmailNotification) {
            throw new \InvalidArgumentException('Argument data should be instance of EmailNotification entity');
        }

        return $this->formWithAjaxReloadHandler->process(
            $data,
            $form,
            $request
        );
    }
}
