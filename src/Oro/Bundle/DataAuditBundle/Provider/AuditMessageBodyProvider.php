<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * It is used to populate audit message body with Security Token data
 */
class AuditMessageBodyProvider
{
    /** @var EntityNameResolver */
    private $entityNameResolver;

    /** @var string */
    private $transactionId;

    public function __construct(
        EntityNameResolver $entityNameResolver
    ) {
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * @param array $insertions
     * @param array $updates
     * @param array $deletions
     * @param array $collectionUpdates
     * @param TokenInterface|null $securityToken
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function prepareMessageBody(
        array $insertions,
        array $updates,
        array $deletions,
        array $collectionUpdates,
        TokenInterface $securityToken = null
    ) {
        if (empty($insertions) && empty($updates) && empty($deletions) && empty($collectionUpdates)) {
            return [];
        }

        $body['entities_inserted'] = $insertions;
        $body['entities_updated'] = $updates;
        $body['entities_deleted'] = $deletions;
        $body['collections_updated'] = $collectionUpdates;

        $body['timestamp'] = time();
        $body['transaction_id'] = $this->getTransactionId();

        if (null !== $securityToken) {
            $user = $securityToken->getUser();
            if ($user instanceof AbstractUser) {
                $body['user_id'] = $user->getId();
                $body['user_class'] = ClassUtils::getClass($user);
                $body['owner_description'] = $this->entityNameResolver->getName($user, 'email');
            }
            if ($securityToken instanceof OrganizationAwareTokenInterface) {
                $body['organization_id'] = $securityToken->getOrganization()->getId();
            }

            if ($securityToken->hasAttribute('IMPERSONATION')) {
                $body['impersonation_id'] = $securityToken->getAttribute('IMPERSONATION');
            }

            if ($securityToken->hasAttribute('owner_description')) {
                $body['owner_description'] = $securityToken->getAttribute('owner_description');
            }
        }

        return $body;
    }

    private function getTransactionId(): string
    {
        if (!$this->transactionId) {
            $this->transactionId = UUIDGenerator::v4();
        }

        return $this->transactionId;
    }
}
