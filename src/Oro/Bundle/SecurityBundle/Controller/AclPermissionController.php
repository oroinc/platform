<?php

namespace Oro\Bundle\SecurityBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AclPermissionController extends Controller
{
    /**
     * @Route(
     *  "/acl-access-levels/{oid}", name="oro_security_access_levels" , requirements={"oid"="\w+:[\w\(\)]+"
     * })
     */
    public function aclAccessLevelsAction($oid)
    {
        if (strpos($oid, 'entity:') === 0) {
            $oid = str_replace('_', '\\', $oid);
        }

        $levels = $this
            ->get('oro_security.acl.manager')
            ->getAccessLevels($oid);
        $translator = $this->get('translator');
        foreach ($levels as $id => $label) {
            $levels[$id] = $translator->trans('oro.security.access-level.' . $label);
        }

        return new JsonResponse(
            $levels
        );
    }

    /**
     * @Route(
     *      "/switch-organization/{id}",
     *      name="oro_security_switch_organization",
     *      defaults={"id"=0}
     * )
     */
    public function switchOrganizationAction($id)
    {
        $result = false;
        $needed = $this->getDoctrine()->getManager()->find("Oro\Bundle\OrganizationBundle\Entity\Organization", $id);
        if ($needed == null || !$needed->isEnabled()) {
            throw $this->createNotFoundException(
                $this->get('translator')->trans('oro.security.organization.not_found')
            );
        }

        $token          = $this->container->get('security.context')->getToken();
        $organizations  = $token->getUser()->getOrganizations();

        foreach ($organizations as $org) {
            if ($needed->getId() == $org->getId()) {
                $result = true;
                break;
            }
        }

        if (!$result) {
            throw new AccessDeniedException(
                $this->get('translator')->trans('oro.security.organization.access_denied')
            );
        }

        $token->setOrganizationContext($needed);
        $this->get('session')->getFlashBag()->add(
            'success',
            $this->get('translator')->trans('oro.security.organization.selected', array('name' => $needed->getName()))
        );

        return $this->redirect($this->generateUrl('oro_default'));
    }
}
