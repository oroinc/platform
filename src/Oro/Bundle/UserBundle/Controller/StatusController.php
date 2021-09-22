<?php

namespace Oro\Bundle\UserBundle\Controller;

use Oro\Bundle\UserBundle\Entity\Manager\StatusManager;
use Oro\Bundle\UserBundle\Entity\Status;
use Oro\Bundle\UserBundle\Form\Handler\StatusHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller for Status entity.
 * @Route("/status")
 */
class StatusController extends AbstractController
{
    /**
     * @Route("/", name="oro_user_status_list", defaults={"limit"=10})
     * @Template
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/create", name="oro_user_status_create")
     * @Template()
     * @param Request $request
     * @return array|Response
     */
    public function createAction(Request $request)
    {
        $result = false;

        if ($this->get(StatusHandler::class)->process($this->getUser(), new Status(), true)) {
            $result = true;
        }

        if ($request->isXmlHttpRequest()) {
            if (!$result) {
                return $this->render(
                    '@OroUser/Status/statusForm.html.twig',
                    [
                         'form' => $this->get('oro_user.form.status')->createView(),
                    ]
                );
            } else {
                return new Response((string) $result);
            }
        } elseif ($result) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->get(TranslatorInterface::class)->trans('oro.user.controller.status.message.saved')
            );

            return $this->redirect($this->generateUrl('oro_user_status_list'));
        }

        return [
            'form' => $this->get('oro_user.form.status')->createView(),
        ];
    }

    /**
     * @Route("/delete/{id}", name="oro_user_status_delete", requirements={"id"="\d+"})
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Status $status, Request $request)
    {
        if ($this->get(StatusManager::class)->deleteStatus($this->getUser(), $status, true)) {
            $request->getSession()->getFlashBag()->add('success', 'Status deleted');
        } else {
            $request->getSession()->getFlashBag()->add('alert', 'Status is not deleted');
        }

        return $this->redirect($this->generateUrl('oro_user_status_list'));
    }

    /**
     * @Route("/set-current/{id}", name="oro_user_status_set_current", requirements={"id"="\d+"})
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function setCurrentStatusAction(Status $status, Request $request)
    {
        $this->get(StatusManager::class)->setCurrentStatus($this->getUser(), $status);
        $request->getSession()->getFlashBag()->add('success', 'Status set');

        return $this->redirect($this->generateUrl('oro_user_status_list'));
    }

    /**
     * @Route("/clear-current", name="oro_user_status_clear_current")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function clearCurrentStatusAction(Request $request)
    {
        $this->get(StatusManager::class)->setCurrentStatus($this->getUser());
        $request->getSession()->getFlashBag()->add('success', 'Status unset');

        return $this->redirect($this->generateUrl('oro_user_status_list'));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                StatusHandler::class,
                StatusManager::class,
                'oro_user.form.status' => Form::class,
            ]
        );
    }
}
