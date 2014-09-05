<?php

namespace Oro\Bundle\SecurityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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
}
