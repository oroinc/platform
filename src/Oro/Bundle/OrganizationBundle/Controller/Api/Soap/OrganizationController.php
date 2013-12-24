<?php

namespace Oro\Bundle\OrganizationBundle\Controller\Api\Soap;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\SoapBundle\Controller\Api\Soap\SoapGetController;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class OrganizationController extends SoapGetController
{
    /**
     * @Soap\Method("getOrganizations")
     * @Soap\Param("page", phpType="int")
     * @Soap\Param("limit", phpType="int")
     * @Soap\Result(phpType = "Oro\Bundle\OrganizationBundle\Entity\OrganizationSoap[]")
     * @AclAncestor("oro_business_unit_view")
     */
    public function cgetAction($page = 1, $limit = 10)
    {
        return $this->handleGetListRequest($page, $limit);
    }

    /**
     * @Soap\Method("getOrganization")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Result(phpType = "Oro\Bundle\OrganizationBundle\Entity\OrganizationSoap")
     * @AclAncestor("oro_business_unit_view")
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->container->get('oro_organization.organization.manager.api');
    }
}
