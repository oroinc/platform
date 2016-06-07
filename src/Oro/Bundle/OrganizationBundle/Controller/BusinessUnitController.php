<?php

namespace Oro\Bundle\OrganizationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Route("/business_unit")
 */
class BusinessUnitController extends Controller
{
    /**
     * Create business_unit form
     *
     * @Route("/create", name="oro_business_unit_create")
     * @Template("OroOrganizationBundle:BusinessUnit:update.html.twig")
     * @Acl(
     *      id="oro_business_unit_create",
     *      type="entity",
     *      class="OroOrganizationBundle:BusinessUnit",
     *      permission="CREATE"
     * )
     */
    public function createAction()
    {
        return $this->update(new BusinessUnit());
    }

    /**
     * @Route("/view/{id}", name="oro_business_unit_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_business_unit_view",
     *      type="entity",
     *      class="OroOrganizationBundle:BusinessUnit",
     *      permission="VIEW"
     * )
     */
    public function viewAction(BusinessUnit $entity)
    {
        return array(
            'entity' => $entity,
            // TODO: it is a temporary solution. In a future it is planned to give an user a choose what to do:
            // completely delete an owner and related entities or reassign related entities to another owner before
            'allow_delete' => !$this->get('oro_organization.owner_deletion_manager')->hasAssignments($entity)
        );
    }

    /**
     * @Route(
     *      "/search/{organizationId}",
     *      name="oro_business_unit_search",
     *      requirements={"organizationId"="\d+"}
     * )
     * Acl(
     *      id="oro_business_unit_view",
     *      type="action",
     *      class="OroOrganizationBundle:BusinessUnit",
     *      permission="VIEW"
     * )
     */
    public function searchAction($organizationId)
    {
        $businessUnits = [];
        if ($organizationId) {
            $businessUnits = $this->get('oro_organization.business_unit_manager')
                ->getBusinessUnitRepo()
                ->getOrganizationBusinessUnitsTree($organizationId);
        }

        return new Response(json_encode($businessUnits));
    }

    /**
     * Edit business_unit form
     *
     * @Route("/update/{id}", name="oro_business_unit_update", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template
     * @Acl(
     *      id="oro_business_unit_update",
     *      type="entity",
     *      class="OroOrganizationBundle:BusinessUnit",
     *      permission="EDIT"
     * )
     */
    public function updateAction(BusinessUnit $entity)
    {
        return $this->update($entity);
    }

    /**
     * @Route(
     *      "/{_format}",
     *      name="oro_business_unit_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @AclAncestor("oro_business_unit_view")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        return array(
            'entity_class' => $this->container->getParameter('oro_organization.business_unit.entity.class')
        );
    }

    /**
     * @param BusinessUnit $entity
     * @return array
     */
    protected function update(BusinessUnit $entity)
    {
        if ($this->get('oro_organization.form.handler.business_unit')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.business_unit.controller.message.saved')
            );

            return $this->get('oro_ui.router')->redirect($entity);
        }

        return array(
            'entity' => $entity,
            'form' => $this->get('oro_organization.form.business_unit')->createView(),
            // TODO: it is a temporary solution. In a future it is planned to give an user a choose what to do:
            // completely delete an owner and related entities or reassign related entities to another owner before
            'allow_delete' =>
                $entity->getId() &&
                !$this->get('oro_organization.owner_deletion_manager')->hasAssignments($entity)
        );
    }

    /**
     * @Route("/widget/info/{id}", name="oro_business_unit_widget_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_business_unit_view")
     */
    public function infoAction(BusinessUnit $entity)
    {
        return array(
            'entity' => $entity,
        );
    }

    /**
     * @Route("/widget/users/{id}", name="oro_business_unit_widget_users", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_user_user_view")
     */
    public function usersAction(BusinessUnit $entity)
    {
        return array(
            'entity' => $entity,
        );
    }
}
