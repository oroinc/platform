<?php

namespace Oro\Bundle\DataAuditBundle\Controller\Api\Soap;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Controller\Api\Soap\SoapGetController;

class AuditController extends SoapGetController
{
    /**
     * @Soap\Method("getAudits")
     * @Soap\Param("page", phpType="int")
     * @Soap\Param("limit", phpType="int")
     * @Soap\Result(phpType = "Oro\Bundle\DataAuditBundle\Entity\Audit[]")
     * @AclAncestor("oro_dataaudit_history")
     *
     * @param int $page
     * @param int $limit
     *
     * @return Audit[]
     */
    public function cgetAction($page = 1, $limit = 10)
    {
        return $this->handleGetListRequest($page, $limit);
    }

    /**
     * @Soap\Method("getAudit")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Result(phpType = "Oro\Bundle\DataAuditBundle\Entity\Audit")
     * @AclAncestor("oro_dataaudit_history")
     *
     * @param int $id
     *
     * @return Audit
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('oro_dataaudit.audit.manager.api');
    }
}
