<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Route("/emailorigin")
 */
class OriginController extends Controller
{
    /**
     * Get list of origins
     *
     * @Route("/list", name="oro_email_emailorigin_list")
     * @AclAncestor("oro_email_origin_view")
     *
     * @return JsonResponse
     */
    public function listAction()
    {
        $originProvider = $this->get('oro_email.datagrid.origin_folder.provider');
        return new JsonResponse($originProvider->getListTypeChoices(true));
    }
}
