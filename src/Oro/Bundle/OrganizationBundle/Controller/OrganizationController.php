<?php

namespace Oro\Bundle\OrganizationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationController extends Controller
{
    /**
     * @Route(
     *      "/{_format}",
     *      name="oro_organization_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @AclAncestor("oro_organization_view")
     * @Template
     */
    public function indexAction(Request $request)
    {
        return array();
    }

    /**
     * @Route("/view/{id}", name="oro_organization_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_organization_view",
     *      type="entity",
     *      class="OroOrganizationBundle:Organization",
     *      permission="VIEW"
     * )
     */
    public function viewAction(Organization $entity)
    {
        return [
            'entity' => $entity,
        ];
    }
}
