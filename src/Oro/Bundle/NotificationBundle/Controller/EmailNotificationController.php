<?php

namespace Oro\Bundle\NotificationBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Form\Type\EmailNotificationType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller for EmailNotification entity.
 * @Route("/email")
 */
class EmailNotificationController extends AbstractController
{
    /**
     * @Route(
     *      "/{_format}",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Acl(
     *      id="oro_notification_emailnotification_view",
     *      type="entity",
     *      class="OroNotificationBundle:EmailNotification",
     *      permission="VIEW"
     * )
     * @Template()
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => EmailNotification::class
        ];
    }

    /**
     * @Route("/update/{id}", requirements={"id"="\d+"}, defaults={"id"=0}))
     * @Acl(
     *      id="oro_notification_emailnotification_update",
     *      type="entity",
     *      class="OroNotificationBundle:EmailNotification",
     *      permission="EDIT"
     * )
     * @Template()
     *
     * @param EmailNotification $entity
     * @param Request $request
     *
     * @return array
     */
    public function updateAction(EmailNotification $entity, Request $request)
    {
        return $this->update($entity, $request);
    }

    /**
     * @Route("/create")
     * @Acl(
     *      id="oro_notification_emailnotification_create",
     *      type="entity",
     *      class="OroNotificationBundle:EmailNotification",
     *      permission="CREATE"
     * )
     * @Template("OroNotificationBundle:EmailNotification:update.html.twig")
     *
     * @param Request $request
     *
     * @return array
     */
    public function createAction(Request $request)
    {
        return $this->update(new EmailNotification(), $request);
    }

    /**
     * @param EmailNotification $entity
     * @param Request $request
     *
     * @return array
     */
    protected function update(EmailNotification $entity, Request $request)
    {
        $form = $this->createForm(EmailNotificationType::class, $entity);
        if ($request->get(EmailNotificationType::NAME)) {
            $form->handleRequest($request);
            $form = $this->createForm(EmailNotificationType::class, $form->getData());
        }

        return $this->get(UpdateHandlerFacade::class)
            ->update(
                $entity,
                $form,
                $this->get(TranslatorInterface::class)
                    ->trans('oro.notification.controller.emailnotification.saved.message'),
                $request,
                'oro_notification.form.handler.email_notification'
            );
    }

    /**
     * @return array|string[]
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                UpdateHandlerFacade::class,
                TranslatorInterface::class,
            ]
        );
    }
}
