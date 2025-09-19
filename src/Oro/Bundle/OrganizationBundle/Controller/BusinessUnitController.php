<?php

namespace Oro\Bundle\OrganizationBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Form\Handler\BusinessUnitHandler;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This controller covers CRUD functionality for Business Unit entity.
 */
#[Route(path: '/business_unit')]
class BusinessUnitController extends AbstractController
{
    /**
     * Create business_unit form
     */
    #[Route(path: '/create', name: 'oro_business_unit_create')]
    #[Template('@OroOrganization/BusinessUnit/update.html.twig')]
    #[Acl(id: 'oro_business_unit_create', type: 'entity', class: BusinessUnit::class, permission: 'CREATE')]
    public function createAction(Request $request)
    {
        return $this->update(new BusinessUnit(), $request);
    }

    #[Route(path: '/view/{id}', name: 'oro_business_unit_view', requirements: ['id' => '\d+'])]
    #[Template('@OroOrganization/BusinessUnit/view.html.twig')]
    #[Acl(id: 'oro_business_unit_view', type: 'entity', class: BusinessUnit::class, permission: 'VIEW')]
    public function viewAction(BusinessUnit $entity)
    {
        return [
            'entity'       => $entity,
            'allow_delete' => $this->isDeleteGranted($entity)
        ];
    }

    #[Route(
        path: '/search/{organizationId}',
        name: 'oro_business_unit_search',
        requirements: ['organizationId' => '\d+']
    )]
    public function searchAction($organizationId)
    {
        $businessUnits = [];
        if ($organizationId) {
            $businessUnits = $this->container->get('doctrine')
                ->getRepository(BusinessUnit::class)
                ->getOrganizationBusinessUnitsTree($organizationId);
        }

        return new Response(json_encode($businessUnits));
    }

    /**
     * Edit business_unit form
     */
    #[Route(
        path: '/update/{id}',
        name: 'oro_business_unit_update',
        requirements: ['id' => '\d+'],
        defaults: ['id' => 0]
    )]
    #[Template('@OroOrganization/BusinessUnit/update.html.twig')]
    #[Acl(id: 'oro_business_unit_update', type: 'entity', class: BusinessUnit::class, permission: 'EDIT')]
    public function updateAction(BusinessUnit $entity, Request $request)
    {
        return $this->update($entity, $request);
    }

    #[Route(
        path: '/{_format}',
        name: 'oro_business_unit_index',
        requirements: ['_format' => 'html|json'],
        defaults: ['_format' => 'html']
    )]
    #[Template('@OroOrganization/BusinessUnit/index.html.twig')]
    #[AclAncestor('oro_business_unit_view')]
    public function indexAction()
    {
        return ['entity_class' => BusinessUnit::class];
    }

    /**
     * @param BusinessUnit $entity
     * @param Request $request
     * @return array
     */
    private function update(BusinessUnit $entity, Request $request)
    {
        if ($this->container->get(BusinessUnitHandler::class)->process($entity)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->container->get(TranslatorInterface::class)->trans('oro.business_unit.controller.message.saved')
            );

            return $this->container->get(Router::class)->redirect($entity);
        }

        return [
            'entity'       => $entity,
            'form'         => $this->container->get('oro_organization.form.business_unit')->createView(),
            'allow_delete' => $entity->getId() && $this->isDeleteGranted($entity)
        ];
    }

    #[Route(path: '/widget/info/{id}', name: 'oro_business_unit_widget_info', requirements: ['id' => '\d+'])]
    #[Template('@OroOrganization/BusinessUnit/info.html.twig')]
    #[AclAncestor('oro_business_unit_view')]
    public function infoAction(BusinessUnit $entity)
    {
        return ['entity' => $entity];
    }

    #[Route(path: '/widget/users/{id}', name: 'oro_business_unit_widget_users', requirements: ['id' => '\d+'])]
    #[Template('@OroOrganization/BusinessUnit/users.html.twig')]
    #[AclAncestor('oro_user_user_view')]
    public function usersAction(BusinessUnit $entity)
    {
        return ['entity' => $entity];
    }

    private function isDeleteGranted(BusinessUnit $entity): bool
    {
        return $this->container->get(EntityDeleteHandlerRegistry::class)
            ->getHandler(BusinessUnit::class)
            ->isDeleteGranted($entity);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                Router::class,
                EntityDeleteHandlerRegistry::class,
                BusinessUnitHandler::class,
                'oro_organization.form.business_unit' => Form::class,
                'doctrine' => ManagerRegistry::class,
            ]
        );
    }
}
