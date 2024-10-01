<?php

namespace Oro\Bundle\EmbeddedFormBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\EmbeddedFormBundle\Form\Type\EmbeddedFormType;
use Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Embedded Form Controller
 */
class EmbeddedFormController extends AbstractController
{
    #[Route(name: 'oro_embedded_form_list')]
    #[Template]
    #[AclAncestor('oro_embedded_form_view')]
    public function indexAction()
    {
        return [];
    }

    #[Route(path: 'create', name: 'oro_embedded_form_create')]
    #[Template('@OroEmbeddedForm/EmbeddedForm/update.html.twig')]
    #[Acl(id: 'oro_embedded_form_create', type: 'entity', class: EmbeddedForm::class, permission: 'CREATE')]
    public function createAction(Request $request)
    {
        return $this->update(new EmbeddedForm(), $request);
    }

    #[Route(
        path: 'delete/{id}',
        name: 'oro_embedded_form_delete',
        requirements: ['id' => '[-\d\w]+'],
        methods: ['DELETE']
    )]
    #[Acl(id: 'oro_embedded_form_delete', type: 'entity', class: EmbeddedForm::class, permission: 'DELETE')]
    #[CsrfProtection()]
    public function deleteAction(EmbeddedForm $entity)
    {
        $em = $this->container->get('doctrine')->getManagerForClass(EmbeddedForm::class);
        $em->remove($entity);
        $em->flush();

        return new JsonResponse('', Response::HTTP_OK);
    }

    #[Route(path: 'default-data/{formType}', name: 'oro_embedded_form_default_data', methods: ['GET'])]
    #[AclAncestor('oro_embedded_form_create')]
    public function defaultDataAction(string $formType)
    {
        $formType = str_replace('_', '\\', $formType);
        $formManager = $this->getFormManager();
        $css = $formManager->getDefaultCssByType($formType);
        $successMessage = $formManager->getDefaultSuccessMessageByType($formType);

        return new JsonResponse(
            [
                'css'            => $css,
                'successMessage' => $successMessage
            ],
            Response::HTTP_OK
        );
    }

    #[Route(path: 'update/{id}', name: 'oro_embedded_form_update', requirements: ['id' => '[-\d\w]+'])]
    #[Template]
    #[Acl(id: 'oro_embedded_form_update', type: 'entity', class: EmbeddedForm::class, permission: 'EDIT')]
    public function updateAction(EmbeddedForm $entity, Request $request)
    {
        return $this->update($entity, $request);
    }

    #[Route(path: 'view/{id}', name: 'oro_embedded_form_view', requirements: ['id' => '[-\d\w]+'])]
    #[Template]
    #[Acl(id: 'oro_embedded_form_view', type: 'entity', class: EmbeddedForm::class, permission: 'VIEW')]
    public function viewAction(EmbeddedForm $entity)
    {
        return [
            'entity' => $entity,
            'label' => $this->getFormManager()->get($entity->getFormType())
        ];
    }

    #[Route(path: 'info/{id}', name: 'oro_embedded_form_info', requirements: ['id' => '[-\d\w]+'])]
    #[Template]
    #[AclAncestor('oro_embedded_form_view')]
    public function infoAction(EmbeddedForm $entity)
    {
        return [
            'entity'  => $entity
        ];
    }

    /**
     * @param EmbeddedForm $entity
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function update(EmbeddedForm $entity, Request $request)
    {
        $form = $this->createForm(EmbeddedFormType::class, $entity);
        $form->handleRequest($this->container->get('request_stack')->getCurrentRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();
            $em = $this->container->get('doctrine')->getManagerForClass(EmbeddedForm::class);
            $em->persist($entity);
            $em->flush();

            $request->getSession()->getFlashBag()->add(
                'success',
                $this->container->get(TranslatorInterface::class)->trans('oro.embeddedform.controller.saved_message')
            );

            return $this->container->get(Router::class)->redirect($entity);
        }

        $formManager = $this->getFormManager();
        return [
            'entity' => $entity,
            'defaultCss' => $formManager->getDefaultCssByType($entity->getFormType()),
            'defaultSuccessMessage' => $formManager->getDefaultSuccessMessageByType($entity->getFormType()),
            'form' => $form->createView()
        ];
    }

    protected function getFormManager(): EmbeddedFormManager
    {
        return $this->container->get(EmbeddedFormManager::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                Router::class,
                EmbeddedFormManager::class,
                'doctrine' => ManagerRegistry::class,
            ]
        );
    }
}
