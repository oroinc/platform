<?php

namespace Oro\Bundle\SecurityBundle\Controller;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Event\OrganizationSwitchAfter;
use Oro\Bundle\SecurityBundle\Event\OrganizationSwitchBefore;
use Oro\Bundle\UserBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * ACL Permission controller
 */
class AclPermissionController extends Controller
{
    /**
     * @Route(
     *  "/acl-access-levels/{oid}/{permission}",
     *  name="oro_security_access_levels",
     *  requirements={"oid"="[\w]+:[\w\:\(\)\|]+", "permission"="[\w/]+"},
     *  defaults={"_format"="json", "permission"=null}
     * )
     * @Template
     *
     * @param string $oid
     * @param string $permission
     *
     * @return array
     */
    public function aclAccessLevelsAction($oid, $permission = null)
    {
        if (ObjectIdentityHelper::getExtensionKeyFromIdentityString($oid) === 'entity') {
            $entity = ObjectIdentityHelper::getClassFromIdentityString($oid);
            if ($entity !== ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
                if (ObjectIdentityHelper::isFieldEncodedKey($entity)) {
                    list($className, $fieldName) = ObjectIdentityHelper::decodeEntityFieldInfo($entity);
                    $oid = ObjectIdentityHelper::encodeIdentityString(
                        'entity',
                        ObjectIdentityHelper::encodeEntityFieldInfo(
                            $this->get('oro_entity.routing_helper')->resolveEntityClass($className),
                            $fieldName
                        )
                    );
                } else {
                    $oid = ObjectIdentityHelper::encodeIdentityString(
                        'entity',
                        $this->get('oro_entity.routing_helper')->resolveEntityClass($entity)
                    );
                }
            }
        }

        $levels = $this
            ->get('oro_security.acl.manager')
            ->getAccessLevels($oid, $permission);

        return ['levels' => $levels];
    }

    /**
     * @Route(
     *      "/switch-organization/{id}",
     *      name="oro_security_switch_organization", defaults={"id"=0}
     * )
     *
     * @param Organization $organization
     * @param Request $request
     *
     * @return RedirectResponse , AccessDeniedException
     */
    public function switchOrganizationAction(Organization $organization, Request $request)
    {
        $token = $this->container->get('security.token_storage')->getToken();
        $user  = $token->getUser();

        if (!$token instanceof OrganizationAwareTokenInterface ||
            !$token->getUser() instanceof User ||
            !$organization->isEnabled() ||
            !$token->getUser()->isBelongToOrganization($organization)
        ) {
            throw new AccessDeniedException(
                $this->get('translator')->trans(
                    'oro.security.organization.access_denied',
                    ['%organization_name%' => $organization->getName()]
                )
            );
        }

        $event = new OrganizationSwitchBefore($user, $token->getOrganization(), $organization);
        $this->get('event_dispatcher')->dispatch(OrganizationSwitchBefore::NAME, $event);
        $organization = $event->getOrganizationToSwitch();

        if (!$user->isBelongToOrganization($organization, true)) {
            $message = $this->get('translator')
                ->trans('oro.security.organization.access_denied', ['%organization_name%' => $organization->getName()]);

            throw new AccessDeniedException($message);
        }

        $token->setOrganization($organization);
        $event = new OrganizationSwitchAfter($user, $organization);
        $this->get('event_dispatcher')->dispatch(OrganizationSwitchAfter::NAME, $event);
        $request->attributes->set('_fullRedirect', true);

        return $this->redirect($this->generateUrl('oro_default'));
    }
}
