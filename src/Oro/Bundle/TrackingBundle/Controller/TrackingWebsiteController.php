<?php

namespace Oro\Bundle\TrackingBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TrackingBundle\Entity\TrackingWebsite;
use Oro\Bundle\TrackingBundle\Form\Handler\TrackingWebsiteHandler;
use Oro\Bundle\TrackingBundle\Form\Type\TrackingWebsiteType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UIBundle\Route\Router;

/**
 * @Route("/tracking/website")
 */
class TrackingWebsiteController extends Controller
{
    /**
     * @Route(
     *      ".{_format}",
     *      name="oro_tracking_website_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     *
     * @Acl(
     *      id="oro_tracking_website_view",
     *      type="entity",
     *      class="OroTrackingBundle:TrackingWebsite",
     *      permission="VIEW"
     * )
     * @Template
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/create", name="oro_tracking_website_create")
     * @Acl(
     *      id="oro_tracking_website_create",
     *      type="entity",
     *      class="OroTrackingBundle:TrackingWebsite",
     *      permission="CREATE"
     * )
     * @Template("OroTrackingBundle:TrackingWebsite:update.html.twig")
     */
    public function createAction()
    {
        return $this->update(new TrackingWebsite());
    }

    /**
     * @Route("/update/{id}", name="oro_tracking_website_update", requirements={"id"="\d+"})
     * @Acl(
     *      id="oro_tracking_website_update",
     *      type="entity",
     *      class="OroTrackingBundle:TrackingWebsite",
     *      permission="EDIT"
     * )
     *
     * @Template()
     */
    public function updateAction(TrackingWebsite $trackingWebsite)
    {
        return $this->update($trackingWebsite);
    }

    /**
     * @Route("/view/{id}", name="oro_tracking_website_view", requirements={"id"="\d+"})
     * @AclAncestor("oro_tracking_website_view")
     * @Template()
     */
    public function viewAction(TrackingWebsite $trackingWebsite)
    {
        return [
            'entity' => $trackingWebsite
        ];
    }

    /**
     * @param TrackingWebsite $trackingWebsite
     * @return null
     */
    public function update(TrackingWebsite $trackingWebsite)
    {
        $form = $this->createForm(
            $this->getFormType(),
            $trackingWebsite
        );

        if ($this->getHandler()->process($trackingWebsite)) {
            $this->getSession()->getFlashBag()->add(
                'success',
                $this->getTranslator()->trans('oro.tracking.tracking_website.saved_message')
            );

            return $this->getRouter()->redirectAfterSave(
                [
                    'route'      => 'oro_tracking_website_update',
                    'parameters' => ['id' => $trackingWebsite->getId()],
                ],
                [
                    'route'      => 'oro_tracking_website_view',
                    'parameters' => ['id' => $trackingWebsite->getId()],
                ]
            );
        }

        return [
            'entity' => $trackingWebsite,
            'form'   => $form->createView()
        ];
    }

    /**
     * @return TrackingWebsiteType
     */
    protected function getFormType()
    {
        return $this->get('oro_tracking.form.type.tracking_website');
    }

    /**
     * @return TrackingWebsiteHandler
     */
    protected function getHandler()
    {
        return $this->get('oro_tracking.form.handler.tracking_website');
    }

    /**
     * @return Translator
     */
    protected function getTranslator()
    {
        return $this->get('translator');
    }

    /**
     * @return Router
     */
    protected function getRouter()
    {
        return $this->get('oro_ui.router');
    }

    /**
     * @return Session
     */
    protected function getSession()
    {
        return $this->get('session');
    }
}
