<?php

namespace Oro\Bundle\SecurityBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Event\OrganizationSwitchAfter;
use Oro\Bundle\SecurityBundle\Event\OrganizationSwitchBefore;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

class AclPermissionController extends Controller
{
    /**
     * @Route(
     *  "/acl-access-levels/{oid}",
     *  name="oro_security_access_levels",
     *  requirements={"oid"="\w+:[\w\(\)]+"},
     *  defaults={"_format"="json"}
     * )
     * @Template
     */
    public function aclAccessLevelsAction($oid)
    {
        if (strpos($oid, 'entity:') === 0) {
            $oid = str_replace('_', '\\', $oid);
        }

        $levels = $this
            ->get('oro_security.acl.manager')
            ->getAccessLevels($oid);

        return array('levels' => $levels);
    }

    /**
     * @Route(
     *      "/switch-organization/{id}",
     *      name="oro_security_switch_organization", defaults={"id"=0}
     * )
     * @param Organization $organization
     *
     * @throws AccessDeniedException, \RuntimeException
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function switchOrganizationAction(Organization $organization)
    {
        $token = $this->container->get('security.context')->getToken();
        $user  = $token->getUser();

        if (!($token instanceof OrganizationContextTokenInterface && $user instanceof User)) {
            $message = sprintf('Impossible to change organization context for "%s" token', get_class($token));

            throw new \RuntimeException($message);
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
