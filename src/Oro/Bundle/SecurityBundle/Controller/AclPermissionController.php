<?php

namespace Oro\Bundle\SecurityBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Event\OrganizationSwitchAfter;
use Oro\Bundle\SecurityBundle\Event\OrganizationSwitchBefore;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

class AclPermissionController extends Controller
{
    /**
     * @Route(
     *  "/acl-access-levels/{oid}/{permission}",
     *  name="oro_security_access_levels",
     *  requirements={"oid"="[\w\+]+:[\w\(\)]+", "permission"="[\w/]+"},
     *  defaults={"_format"="json", "permission"=null}
     * )
     * @Template
     *
     * @param string $oid
     * @param string $permission
     *
     * @return array
     */
    public function aclAccessLevelsAction($oid)
    {
        if (strpos($oid, 'entity:') === 0) {
            $entity = substr($oid, 7);
            if ($entity !== ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
                $oid = 'entity:' . $this->get('oro_entity.routing_helper')->resolveEntityClass($entity);
            }
        }

        $levels = $this
            ->get('oro_security.acl.manager')
            ->getAccessLevels($oid);

        return ['levels' => $levels];
    }

    /**
     * @Route(
     *      "/switch-organization/{id}",
     *      name="oro_security_switch_organization", defaults={"id"=0}
     * )
     *
     * @param Organization $organization
     *
     * @return RedirectResponse , AccessDeniedException
     */
    public function switchOrganizationAction(Organization $organization)
    {
        $token = $this->container->get('security.context')->getToken();
        $user  = $token->getUser();

        if (!$token instanceof OrganizationContextTokenInterface ||
            !$token->getUser() instanceof User ||
            !$organization->isEnabled() ||
            !$token->getUser()->getOrganizations()->contains($organization)
        ) {
            throw new AccessDeniedException(
                $this->get('translator')->trans(
                    'oro.security.organization.access_denied',
                    ['%organization_name%' => $organization->getName()]
                )
            );
        }

        $event = new OrganizationSwitchBefore($user, $token->getOrganizationContext(), $organization);
        $this->get('event_dispatcher')->dispatch(OrganizationSwitchBefore::NAME, $event);
        $organization = $event->getOrganizationToSwitch();

        if (!$user->getOrganizations(true)->contains($organization)) {
            $message = $this->get('translator')
                ->trans('oro.security.organization.access_denied', ['%organization_name%' => $organization->getName()]);

            throw new AccessDeniedException($message);
        }

        $token->setOrganizationContext($organization);
        $event = new OrganizationSwitchAfter($user, $organization);
        $this->get('event_dispatcher')->dispatch(OrganizationSwitchAfter::NAME, $event);

        return $this->redirect($this->generateUrl('oro_default'));
    }
}
