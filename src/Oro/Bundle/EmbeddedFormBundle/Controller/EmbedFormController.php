<?php

namespace Oro\Bundle\EmbeddedFormBundle\Controller;


use Doctrine\ORM\EntityManager;
use OroCRM\Bundle\ContactUsBundle\Entity\ContactRequest;
use OroCRM\Bundle\ContactUsBundle\Form\Type\ContactRequestType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class EmbedFormController extends Controller
{
    /**
     * @Route("submit", name="oro_embedded_form_submit")
     * @Template
     */
    public function formAction(Request $request)
    {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $contactRequest = new ContactRequest();
        $channel = $em->getRepository('OroIntegrationBundle:Channel')->find(1);
        $contactRequest->setChannel($channel);

        $form = $this->createForm(new ContactRequestType(), $contactRequest);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $entity = $form->getData();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('oro_embedded_form_success'));
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("success", name="oro_embedded_form_success")
     * @Template
     */
    public function formSuccessAction()
    {
        return [];
    }
} 
