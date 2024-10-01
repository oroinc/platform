<?php

namespace Oro\Bundle\EntityConfigBundle\Controller;

use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * EntityBundle controller.
 */
#[Route(path: '/entity/config')]
class AuditController extends AbstractController
{
    /**
     *
     * @param $entity
     * @param $id
     * @return array
     */
    #[Route(
        path: '/audit/{entity}/{id}/{_format}',
        name: 'oro_entityconfig_audit',
        requirements: ['entity' => '[a-zA-Z0-9_]+', 'id' => '\d+'],
        defaults: ['entity' => 'entity', 'id' => 0, '_format' => 'html']
    )]
    #[Template('@OroDataAudit/Audit/widget/history.html.twig')]
    #[AclAncestor('oro_dataaudit_view')]
    public function auditAction($entity, $id)
    {
        return [
            'gridName'    => 'audit-log-grid',
            'entityClass' => $this->container->get(EntityRoutingHelper::class)->resolveEntityClass($entity),
            'entityId'    => $id,
        ];
    }

    /**
     *
     * @param $entity
     * @param $id
     * @return array
     */
    #[Route(
        path: '/audit_field/{entity}/{id}/{_format}',
        name: 'oro_entityconfig_audit_field',
        requirements: ['entity' => '[a-zA-Z0-9_]+', 'id' => '\d+'],
        defaults: ['entity' => 'entity', 'id' => 0, '_format' => 'html']
    )]
    #[Template('@OroDataAudit/Audit/widget/history.html.twig')]
    #[AclAncestor('oro_dataaudit_view')]
    public function auditFieldAction($entity, $id)
    {
        /** @var FieldConfigModel $fieldName */
        $fieldName = $this->getConfigManager()
            ->getEntityManager()
            ->getRepository(FieldConfigModel::class)
            ->findOneBy(['id' => $id]);

        return [
            'gridName'    => 'auditfield-log-grid',
            'entityClass' => $this->container->get(EntityRoutingHelper::class)->resolveEntityClass($entity),
            'fieldName'   => $fieldName->getFieldName(),
            'entityId'    => $id,
        ];
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager()
    {
        return $this->container->get(ConfigManager::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ConfigManager::class,
            EntityRoutingHelper::class,
        ];
    }
}
