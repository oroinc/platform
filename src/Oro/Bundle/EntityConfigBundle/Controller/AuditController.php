<?php

namespace Oro\Bundle\EntityConfigBundle\Controller;

use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * EntityBundle controller.
 * @Route("/entity/config")
 */
class AuditController extends AbstractController
{
    /**
     * @Route(
     *      "/audit/{entity}/{id}/{_format}",
     *      name="oro_entityconfig_audit",
     *      requirements={"entity"="[a-zA-Z0-9_]+", "id"="\d+"},
     *      defaults={"entity"="entity", "id"=0, "_format" = "html"}
     * )
     * @Template("@OroDataAudit/Audit/widget/history.html.twig")
     * @AclAncestor("oro_dataaudit_view")
     *
     * @param $entity
     * @param $id
     * @return array
     */
    public function auditAction($entity, $id)
    {
        return [
            'gridName'    => 'audit-log-grid',
            'entityClass' => $this->get(EntityRoutingHelper::class)->resolveEntityClass($entity),
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
     * @Template("@OroDataAudit/Audit/widget/history.html.twig")
     * @AclAncestor("oro_dataaudit_view")
     *
     * @param $entity
     * @param $id
     * @return array
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
            'entityClass' => $this->get(EntityRoutingHelper::class)->resolveEntityClass($entity),
            'fieldName'   => $fieldName->getFieldName(),
            'entityId'    => $id,
        ];
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager()
    {
        return $this->get(ConfigManager::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            ConfigManager::class,
            EntityRoutingHelper::class,
        ];
    }
}
