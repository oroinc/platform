<?php

namespace Oro\Bundle\OrganizationBundle\Controller;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Form\Handler\OrganizationHandler;
use Oro\Bundle\OrganizationBundle\Form\Type\OrganizationType;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles organization update action
 */
class OrganizationController extends AbstractController
{
    /**
     * Edit organization form
     */
    #[Route(path: '/update_current', name: 'oro_organization_update_current')]
    #[Template('@OroOrganization/Organization/update.html.twig')]
    #[Acl(id: 'oro_organization_update', type: 'entity', class: Organization::class, permission: 'EDIT')]
    public function updateCurrentAction(Request $request)
    {
        /** @var UsernamePasswordOrganizationToken $token */
        $token = $this->container->get(TokenStorageInterface::class)->getToken();
        $organization = $token->getOrganization();

        return $this->update($organization, $request);
    }

    /**
     * @param Organization $entity
     * @param Request $request
     * @return array
     */
    protected function update(Organization $entity, Request $request)
    {
        $organizationForm = $this->container->get(FormFactoryInterface::class)->createNamed(
            'oro_organization_form',
            OrganizationType::class,
            $entity
        );

        if ($this->container->get(OrganizationHandler::class)->process($entity, $organizationForm)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->container->get(TranslatorInterface::class)->trans('oro.organization.controller.message.saved')
            );

            return $this->container->get(Router::class)->redirect($entity);
        }

        return [
            'entity' => $entity,
            'form' => $organizationForm->createView(),
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            TokenStorageInterface::class,
            OrganizationHandler::class,
            FormFactoryInterface::class,
            TranslatorInterface::class,
            Router::class
        ]);
    }
}
