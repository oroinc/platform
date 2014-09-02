<?php

namespace Oro\Bundle\EmbeddedFormBundle\Controller;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\PropertyAccess\PropertyAccess;

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
        $response->setEtag($formEntity->getId() . $formEntity->getUpdatedAt()->format(\DateTime::ISO8601));
        if ($response->isNotModified($request)) {
            return $response;
        }

        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        /** @var EmbeddedFormManager $formManager */
        $formManager = $this->get('oro_embedded_form.manager');
        $form        = $formManager->createForm($formEntity->getFormType());

        $form->handleRequest($request);
        if ($form->isValid()) {
            $entity = $form->getData();

            /**
             * Set owner ID (current organization) to concrete form entity
             */
            $entityClass      = ClassUtils::getClass($entity);
            $config           = $this->get('oro_entity_config.provider.ownership');
            $entityConfig     = $config->getConfig($entityClass);
            $formEntityConfig = $config->getConfig($formEntity);

            if (
                $entityConfig->has('owner_field_name') &&
                $entityConfig->get('owner_type') == OwnershipType::OWNER_TYPE_ORGANIZATION
            ) {
                $accessor = PropertyAccess::createPropertyAccessor();
                $accessor->setValue(
                    $entity,
                    $entityConfig->get('owner_field_name'),
                    $accessor->getValue($formEntity, $formEntityConfig->get('owner_field_name'))
                );
            }
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
