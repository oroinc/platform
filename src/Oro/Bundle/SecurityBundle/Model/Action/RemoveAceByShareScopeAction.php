<?php

namespace Oro\Bundle\SecurityBundle\Model\Action;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Security\Acl\Model\AclCacheInterface;

use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class RemoveAceByShareScopeAction extends AbstractAction
{
    /** @var AceQueryInterface */
    protected $qbManager;

    /** @var ObjectManager */
    protected $manager;

    /** @var AclCacheInterface */
    protected $aclCache;

    /**
     * @param ContextAccessor   $contextAccessor
     * @param AceQueryInterface $qbManager
     * @param ObjectManager     $manager
     * @param AclCacheInterface $aclCache
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        AceQueryInterface $qbManager,
        ObjectManager $manager,
        AclCacheInterface $aclCache
    ) {
        parent::__construct($contextAccessor);

        $this->qbManager = $qbManager;
        $this->manager   = $manager;
        $this->aclCache  = $aclCache;
    }

    /**
     * {@inheritdoc}
     */
    protected function isAllowed($context)
    {
        $isAllowed = false;
        if ($context->old &&
            isset($context->old['security']['share_scopes']) &&
            $context->new &&
            isset($context->new['security']['share_scopes'])
        ) {
            $isAllowed = true;
        }

        return $isAllowed && parent::isAllowed($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $aclClass = $this->manager->getRepository('OroSecurityBundle:AclClass')
            ->findOneBy(['classType' => $context->data->getClassName()]);
        $removeScopes = array_diff(
            $context->old['security']['share_scopes'],
            $context->new['security']['share_scopes']
        );

        if (!$aclClass || empty($removeScopes)) {
            return;
        }

        $qb = $this->qbManager->getRemoveAceQueryBuilder($aclClass, $removeScopes);
        $qb->getQuery()->execute();
        $this->aclCache->clearCache();
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        return $this;
    }
}
