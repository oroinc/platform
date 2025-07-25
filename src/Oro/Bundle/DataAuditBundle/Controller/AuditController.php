<?php

namespace Oro\Bundle\DataAuditBundle\Controller;

use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Audits list and audit history actions
 */
class AuditController extends AbstractController
{
    /**
     * @param Request $request
     * @return array
     */
    #[Route(
        path: '/{_format}',
        name: 'oro_dataaudit_index',
        requirements: ['_format' => 'html|json'],
        defaults: ['_format' => 'html']
    )]
    #[Template]
    #[AclAncestor('oro_dataaudit_view')]
    public function indexAction(Request $request)
    {
        return [];
    }

    /**
     * @param string $entity
     * @param string|int $id
     * @return array
     */
    #[Route(
        path: '/history/{entity}/{id}/{_format}',
        name: 'oro_dataaudit_history',
        requirements: ['entity' => '[a-zA-Z0-9_]+', 'id' => '[a-zA-Z0-9_-]+'],
        defaults: ['entity' => 'entity', 'id' => 0, '_format' => 'html']
    )]
    #[Template]
    #[Acl(id: 'oro_dataaudit_view', type: 'entity', class: AbstractAudit::class, permission: 'VIEW')]
    public function historyAction($entity, $id)
    {
        return [
            'gridName' => 'audit-history-grid',
            'entityClass' => $entity,
            'entityId' => $id,
        ];
    }
}
