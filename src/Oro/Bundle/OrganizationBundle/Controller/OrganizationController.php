<?php

namespace Oro\Bundle\OrganizationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationController extends Controller
{
    /**
     * Edit organization form
     *
     * @Route("/update_current", name="oro_organization_update_current")
     * @Template("OroOrganizationBundle:Organization:update.html.twig")
     * @Acl(
     *      id="oro_organization_update",
     *      type="entity",
     *      class="OroOrganizationBundle:Organization",
     *      permission="EDIT"
     * )
     */
    public function updateCurrentAction()
    {
        /** @var UsernamePasswordOrganizationToken $token */
        $token = $this->get('security.context')->getToken();
        $organization = $token->getOrganizationContext();

        return $this->update($organization);
    }

    /**
     * @param Organization $entity
     * @return array
     */
    protected function update(Organization $entity)
    {
        if ($this->get('oro_organization.form.handler.organization')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.organization.controller.message.saved')
            );

            return $this->get('oro_ui.router')->redirect($entity);
        }

        return [
            'entity' => $entity,
            'form' => $this->get('oro_organization.form.organization')->createView(),
        ];
    }
}
