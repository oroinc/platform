<?php

namespace Oro\Bundle\OrganizationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * @Route("/organization")
 */
class OrganizationController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_organisation_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_organisation_view",
     *      type="entity",
     *      class="OroOrganizationBundle:Organisation",
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
