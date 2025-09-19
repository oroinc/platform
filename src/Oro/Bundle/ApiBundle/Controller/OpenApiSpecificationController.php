<?php

namespace Oro\Bundle\ApiBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Async\Topic\CreateOpenApiSpecificationTopic;
use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Oro\Bundle\ApiBundle\Form\Type\OpenApiSpecificationCloneType;
use Oro\Bundle\ApiBundle\Form\Type\OpenApiSpecificationType;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller for OpenAPI specification management.
 */
#[Route(path: '/openapi-specification')]
class OpenApiSpecificationController
{
    public function __construct(
        private FormFactoryInterface $formFactoty,
        private UpdateHandlerFacade $updateHandlerFacade,
        private EntityDeleteHandlerRegistry $deleteHandlerRegistry,
        private TranslatorInterface $translator,
        private ManagerRegistry $doctrine,
        private MessageProducerInterface $producer
    ) {
    }

    #[Route(path: '/', name: 'oro_openapi_specification_index')]
    #[AclAncestor('oro_openapi_specification_view')]
    #[Template('@OroApi/OpenApiSpecification/index.html.twig')]
    public function indexAction(): array
    {
        return ['entity_class' => OpenApiSpecification::class];
    }

    #[Route(path: '/view/{id}', name: 'oro_openapi_specification_view', requirements: ['id' => '\d+'])]
    #[Acl(
        id: 'oro_openapi_specification_view',
        type: 'entity',
        class: OpenApiSpecification::class,
        permission: 'VIEW'
    )]
    #[Template('@OroApi/OpenApiSpecification/view.html.twig')]
    public function viewAction(OpenApiSpecification $entity): array
    {
        return ['entity' => $entity];
    }

    #[Route(path: '/create', name: 'oro_openapi_specification_create')]
    #[Acl(
        id: 'oro_openapi_specification_create',
        type: 'entity',
        class: OpenApiSpecification::class,
        permission: 'CREATE'
    )]
    #[Template('@OroApi/OpenApiSpecification/create.html.twig')]
    public function createAction(Request $request): array|Response
    {
        $entity = new OpenApiSpecification();

        return $this->updateHandlerFacade->update(
            $entity,
            $this->formFactoty->create(OpenApiSpecificationType::class, $entity),
            $this->translator->trans('oro.api.open_api.specification.requested_message'),
            $request
        );
    }

    #[Route(path: '/update/{id}', name: 'oro_openapi_specification_update', requirements: ['id' => '\d+'])]
    #[Acl(
        id: 'oro_openapi_specification_update',
        type: 'entity',
        class: OpenApiSpecification::class,
        permission: 'EDIT'
    )]
    #[Template('@OroApi/OpenApiSpecification/update.html.twig')]
    public function updateAction(OpenApiSpecification $entity, Request $request): array|Response
    {
        if ($entity->isPublished()) {
            throw new AccessDeniedHttpException('The already published OpenAPI specification cannot be changed.');
        }

        return $this->updateHandlerFacade->update(
            $entity,
            $this->formFactoty->create(OpenApiSpecificationType::class, $entity),
            $this->translator->trans('oro.api.open_api.specification.saved_message'),
            $request
        );
    }

    #[Route(path: '/delete/{id}', name: 'oro_openapi_specification_delete', requirements: ['id' => '\d+'])]
    #[Acl(
        id: 'oro_openapi_specification_delete',
        type: 'entity',
        class: OpenApiSpecification::class,
        permission: 'DELETE'
    )]
    public function deleteAction(OpenApiSpecification $entity): Response
    {
        $this->deleteHandlerRegistry->getHandler(OpenApiSpecification::class)->delete($entity);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/clone/{id}', name: 'oro_openapi_specification_clone', requirements: ['id' => '\d+'])]
    #[AclAncestor('oro_openapi_specification_create')]
    #[Template('@OroApi/OpenApiSpecification/create.html.twig')]
    public function cloneAction(OpenApiSpecification $entity, Request $request): array|Response
    {
        $newEntity = new OpenApiSpecification();
        $newEntity->setOrganization($entity->getOrganization());
        $newEntity->setOwner($entity->getOwner());
        $newEntity->setName($entity->getName());
        $newEntity->setPublicSlug($entity->getPublicSlug());
        $newEntity->setView($entity->getView());
        $newEntity->setFormat($entity->getFormat());
        $newEntity->setEntities($entity->getEntities());
        $newEntity->setServerUrls($entity->getServerUrls());

        return $this->updateHandlerFacade->update(
            $newEntity,
            $this->formFactoty->create(OpenApiSpecificationCloneType::class, $newEntity),
            $this->translator->trans('oro.api.open_api.specification.requested_message'),
            $request
        );
    }

    #[Route(
        path: '/renew/{id}',
        name: 'oro_openapi_specification_renew',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    #[AclAncestor('oro_openapi_specification_create')]
    public function renewAction(OpenApiSpecification $entity): Response
    {
        if ($entity->getStatus() === OpenApiSpecification::STATUS_CREATING) {
            return new JsonResponse(['successful' => false]);
        }

        if ($entity->getStatus() !== OpenApiSpecification::STATUS_RENEWING) {
            $entity->setStatus(OpenApiSpecification::STATUS_RENEWING);
            $this->getEntityManager()->flush();
        }
        $this->producer->send(
            CreateOpenApiSpecificationTopic::getName(),
            ['entityId' => $entity->getId(), 'renew' => true]
        );

        return new JsonResponse([
            'successful' => true,
            'message'    => $this->translator->trans('oro.api.open_api.specification.renew_requested_message')
        ]);
    }

    #[Route(
        path: '/publish/{id}',
        name: 'oro_openapi_specification_publish',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    #[AclAncestor('oro_openapi_specification_create')]
    public function publishAction(OpenApiSpecification $entity): Response
    {
        if ($entity->isPublished() || null === $entity->getSpecificationCreatedAt()) {
            return new JsonResponse(['successful' => false]);
        }

        $entity->setPublished(true);
        $this->getEntityManager()->flush();

        return new JsonResponse([
            'successful' => true,
            'message'    => $this->translator->trans(sprintf(
                'oro.api.open_api.specification.published_%s_message',
                $entity->getPublicSlug() ? 'public' : 'private'
            ))
        ]);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(OpenApiSpecification::class);
    }
}
