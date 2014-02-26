<?php

namespace Oro\Bundle\SecurityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

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
}
