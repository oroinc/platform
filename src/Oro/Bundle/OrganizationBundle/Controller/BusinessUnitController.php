<?php

namespace Oro\Bundle\OrganizationBundle\Controller;

use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Form\Handler\BusinessUnitHandler;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This controller covers CRUD functionality for Business Unit entity.
 * @Route("/business_unit")
 */
class BusinessUnitController extends AbstractController
{
    /**
     * Create business_unit form
     *
     * @Route("/create", name="oro_business_unit_create")
     * @Template("@OroOrganization/BusinessUnit/update.html.twig")
     * @Acl(
     *      id="oro_business_unit_create",
     *      type="entity",
     *      class="OroOrganizationBundle:BusinessUnit",
     *      permission="CREATE"
     * )
     */
    public function createAction(Request $request)
    {
        return $this->update(new BusinessUnit(), $request);
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
        return [
            'entity'       => $entity,
            'allow_delete' => $this->isDeleteGranted($entity)
        ];
    }

    /**
     * @Route(
     *      "/search/{organizationId}",
     *      name="oro_business_unit_search",
     *      requirements={"organizationId"="\d+"}
     * )
     */
    public function searchAction($organizationId)
    {
        $businessUnits = [];
        if ($organizationId) {
            $businessUnits = $this->getDoctrine()
                ->getRepository(BusinessUnit::class)
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
    public function updateAction(BusinessUnit $entity, Request $request)
    {
        return $this->update($entity, $request);
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
    public function indexAction()
    {
        return ['entity_class' => BusinessUnit::class];
    }

    /**
     * @param BusinessUnit $entity
     * @param Request $request
     * @return array
     */
    private function update(BusinessUnit $entity, Request $request)
    {
        if ($this->get(BusinessUnitHandler::class)->process($entity)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->get(TranslatorInterface::class)->trans('oro.business_unit.controller.message.saved')
            );

            return $this->get(Router::class)->redirect($entity);
        }

        return [
            'entity'       => $entity,
            'form'         => $this->get('oro_organization.form.business_unit')->createView(),
            'allow_delete' => $entity->getId() && $this->isDeleteGranted($entity)
        ];
    }

    /**
     * @Route("/widget/info/{id}", name="oro_business_unit_widget_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_business_unit_view")
     */
    public function infoAction(BusinessUnit $entity)
    {
        return ['entity' => $entity];
    }

    /**
     * @Route("/widget/users/{id}", name="oro_business_unit_widget_users", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_user_user_view")
     */
    public function usersAction(BusinessUnit $entity)
    {
        return ['entity' => $entity];
    }

    private function isDeleteGranted(BusinessUnit $entity): bool
    {
        return $this->get(EntityDeleteHandlerRegistry::class)
            ->getHandler(BusinessUnit::class)
            ->isDeleteGranted($entity);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                Router::class,
                EntityDeleteHandlerRegistry::class,
                BusinessUnitHandler::class,
                'oro_organization.form.business_unit' => Form::class,
            ]
        );
    }
}
