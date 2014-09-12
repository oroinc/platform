<?php

namespace Oro\Bundle\EmbeddedFormBundle\Controller;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\NotificationBundle\Entity\Event;
use Oro\Bundle\EmbeddedFormBundle\Event\EmbeddedFormSubmitAfterEvent;
use Oro\Bundle\EmbeddedFormBundle\Event\EmbeddedFormSubmitBeforeEvent;
use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager;

class EmbedFormController extends Controller
{
    /**
     * @Route("/submit/{id}", name="oro_embedded_form_submit", requirements={"id"="[-\d\w]+"})
     */
    public function formAction(EmbeddedForm $formEntity, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setEtag($formEntity->getId() . $formEntity->getUpdatedAt()->format(\DateTime::ISO8601));
        if ($response->isNotModified($request)) {
            return $response;
        }

        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        /** @var EmbeddedFormManager $formManager */
        $formManager = $this->get('oro_embedded_form.manager');
        $form        = $formManager->createForm($formEntity->getFormType());

        if (in_array($request->getMethod(), ['POST', 'PUT'])) {
            $dataClass = $form->getConfig()->getOption('data_class');
            if (isset($dataClass) && class_exists($dataClass)) {
                $ref = new \ReflectionClass($dataClass);
                $data = $ref->getConstructor()->getNumberOfRequiredParameters() ?
                    $ref->newInstanceWithoutConstructor() :
                    $ref->newInstance();
                $form->setData($data);
            } else {
                $data = [];
            }
            $event = new EmbeddedFormSubmitBeforeEvent($data, $formEntity);
            $eventDispatcher = $this->get('event_dispatcher');
            $eventDispatcher->dispatch(EmbeddedFormSubmitBeforeEvent::EVENT_NAME, $event);
            $form->submit($request);

            $event = new EmbeddedFormSubmitAfterEvent($data, $formEntity, $form);
            $eventDispatcher->dispatch(EmbeddedFormSubmitAfterEvent::EVENT_NAME, $event);
        }

        if ($form->isValid()) {
            $entity = $form->getData();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('oro_embedded_form_success', ['id' => $formEntity->getId()]));
        }

        $this->render(
            'OroEmbeddedFormBundle:EmbedForm:form.html.twig',
            [
                'form'             => $form->createView(),
                'formEntity'       => $formEntity,
                'customFormLayout' => $formManager->getCustomFormLayoutByFormType($formEntity->getFormType())
            ],
            $response
        );

        return $response;
    }

    /**
     * @Route("/success/{id}", name="oro_embedded_form_success", requirements={"id"="[-\d\w]+"})
     * @Template
     */
    public function formSuccessAction(EmbeddedForm $formEntity)
    {
        return [
            'formEntity' => $formEntity
        ];
    }
}
