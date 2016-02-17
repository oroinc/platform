<?php

namespace Oro\Bundle\SecurityBundle\Migration;

use Psr\Log\LoggerInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

use Oro\Bundle\SecurityBundle\Acl\Dbal\MutableAclProvider;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

/**
 * Deletes all ACL related data including class information for the given object identity.
 */
class DeleteAclMigrationQuery implements MigrationQuery
{
    /** @var MutableAclProvider */
    protected $aclProvider;

    /** @var ObjectIdentityInterface */
    protected $oid;

    /**
     * @param ContainerInterface      $container The container
     * @param ObjectIdentityInterface $oid       The object identity
     */
    public function __construct(ContainerInterface $container, ObjectIdentityInterface $oid)
    {
        $this->aclProvider = $container->get(
            'security.acl.dbal.provider',
            ContainerInterface::NULL_ON_INVALID_REFERENCE
        );
        $this->oid         = $oid;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->aclProvider
            ? $this->buildDescription()
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        if ($this->aclProvider) {
            $logger->info($this->buildDescription());
            $this->aclProvider->deleteAclClass($this->oid);
        }
    }

    /**
     * @return string
     */
    protected function buildDescription()
    {
        return sprintf('Remove ACL for %s.', (string)$this->oid);
    }
}
