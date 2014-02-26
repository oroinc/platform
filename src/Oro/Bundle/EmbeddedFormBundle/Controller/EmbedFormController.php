<?php

namespace Oro\Bundle\EmbeddedFormBundle\Controller;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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
        $response->setMaxAge(30);
        $response->setLastModified($formEntity->getUpdatedAt());
        $response->setEtag($formEntity->getId() . $formEntity->getUpdatedAt()->format(\DateTime::ISO8601));
        if ($response->isNotModified($request)) {
            return $response;
        }

        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        /** @var EmbeddedFormManager $formManager */
        $formManager = $this->get('oro_embedded_form.manager');
        $form        = $formManager->createForm($formEntity->getFormType());

        // avoid coming empty channel from client side
        if ($form->has('channel')) {
            $type = $form->get('channel')->getConfig()->getType()->getInnerType();
            $configOptions = $form->get('channel')->getConfig()->getOptions();

            $channel = $formEntity->getChannel();
            $channelClassName = ClassUtils::getClass($channel);

            /**
             * @var ClassMetadataInfo $channelMetadata
             */
            $channelMetadata = $this->getDoctrine()
                ->getManagerForClass($channelClassName)
                ->getClassMetadata($channelClassName);

            $configOptions = array_merge(
                $configOptions,
                [
                    'auto_initialize' => false,
                    'property'        => $channelMetadata->getSingleIdentifierFieldName(),
                    'empty_data'      => $channel
                ]
            );
            $form->add('channel', $type, $configOptions);
        }

        $form->handleRequest($request);
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
            'entity' => $formEntity
        ];
    }
}
