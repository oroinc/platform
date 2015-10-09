<?php

namespace Oro\Bundle\EntityConfigBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * EntityBundle controller.
 * @Route("/entity/config")
 */
class AuditController extends Controller
{
    /**
     * @Route(
     *      "/audit/{entity}/{id}/{_format}",
     *      name="oro_entityconfig_audit",
     *      requirements={"entity"="[a-zA-Z0-9_]+", "id"="\d+"},
     *      defaults={"entity"="entity", "id"=0, "_format" = "html"}
     * )
     * @Template("OroDataAuditBundle:Audit/widget:history.html.twig")
     * @AclAncestor("oro_dataaudit_history")
     *
     * @param $entity
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function auditAction($entity, $id)
    {
        return [
            'gridName'    => 'audit-log-grid',
            'entityClass' => $this->get('oro_entity.routing_helper')->resolveEntityClass($entity),
            'entityId'    => $id,
        ];
    }

    /**
     * @Route(
     *      "/audit_field/{entity}/{id}/{_format}",
     *      name="oro_entityconfig_audit_field",
     *      requirements={"entity"="[a-zA-Z0-9_]+", "id"="\d+"},
     *      defaults={"entity"="entity", "id"=0, "_format" = "html"}
     * )
     * @Template("OroDataAuditBundle:Audit/widget:history.html.twig")
     * @AclAncestor("oro_dataaudit_history")
     *
     * @param $entity
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function auditFieldAction($entity, $id)
    {
        /** @var FieldConfigModel $fieldName */
        $fieldName = $this->getConfigManager()
            ->getEntityManager()
            ->getRepository('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->findOneBy(['id' => $id]);

        return [
            'gridName'    => 'auditfield-log-grid',
            'entityClass' => $this->get('oro_entity.routing_helper')->resolveEntityClass($entity),
            'fieldName'   => $fieldName->getFieldName(),
            'entityId'    => $id,
        ];
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager()
    {
        return $this->get('oro_entity_config.config_manager');
    }
}
