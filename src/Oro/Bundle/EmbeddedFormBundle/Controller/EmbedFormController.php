<?php

namespace Oro\Bundle\EmbeddedFormBundle\Controller;


use Oro\Bundle\EmbeddedFormBundle\Form\Type\ContactRequestType;
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
        $form = $this->createForm(new ContactRequestType());
        $form->handleRequest($request);
        if ($form->isValid()) {
            $entity = $form->getData();
            $em = $this->get('doctrine.orm.entity_manager');
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('oro_embed_form_success'));
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
