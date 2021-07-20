<?php

namespace Oro\Bundle\DraftBundle\Helper;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Provides the ability to create the correct permissions for working with the ACL for draft.
 */
class DraftPermissionHelper
{
    private const PERMISSION_OWNER_SUFFIX = 'DRAFT';
    private const PERMISSION_SUFFIX = 'ALL_DRAFTS';

    /**
     * @var TokenAccessorInterface
     */
    private $tokenAccessor;

    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    public function generatePermissions(DraftableInterface $object, string $permission): string
    {
        return $this->isUserOwned($object)
            ? $this->generateOwnerPermission($permission)
            : $this->generateGlobalPermission($permission);
    }

    public function isUserOwned(DraftableInterface $object): bool
    {
        return $object->getDraftOwner() === $this->tokenAccessor->getUser();
    }

    public function generateOwnerPermission(string $permission): string
    {
        return sprintf('%s_%s', $permission, self::PERMISSION_OWNER_SUFFIX);
    }

    public function generateGlobalPermission(string $permission): string
    {
        return sprintf('%s_%s', $permission, self::PERMISSION_SUFFIX);
    }
}
