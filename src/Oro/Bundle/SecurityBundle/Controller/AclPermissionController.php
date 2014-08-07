<?php

namespace Oro\Bundle\SecurityBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

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
     *      name="oro_security_switch_organization", defaults={"id"=0}
     * )
     * @ParamConverter("organization", class="OroOrganizationBundle:Organization")
     * @throws NotFoundHttpException, AccessDeniedException
     */
    public function switchOrganizationAction(Organization $organization)
    {
        if ($organization == null || !$organization->isEnabled()) {
            throw $this->createNotFoundException(
                $this->get('translator')->trans('oro.security.organization.not_found')
            );
        }

        $token         = $this->container->get('security.context')->getToken();
        $organizations = $token->getUser()->getOrganizations();

        if (!$organizations->contains($organization)) {
            throw new AccessDeniedException(
                $this->get('translator')->trans('oro.security.organization.access_denied')
            );
        }

        $token->setOrganizationContext($organization);
        $this->get('session')->getFlashBag()->add(
            'success',
            $this->get('translator')->trans(
                'oro.security.organization.selected',
                array('name' => $organization->getName())
            )
        );

        return $this->redirect($this->generateUrl('oro_default'));
    }

    /**
     * @Route(
     *      "/organization",
     *      name="oro_security_current_organization"
     * )
     * @Template("OroSecurityBundle:Organization:name.html.twig")
     */
    public function currentOrganizationNameAction()
    {
        return [
            'name' => $this->container->get('security.context')->getToken()->getOrganizationContext()->getName()
        ];
    }
}
