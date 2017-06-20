<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class UserWithoutCurrentHandler extends UserSearchHandler
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     * @param AttachmentManager      $attachmentManager
     * @param array                  $userEntityName
     * @param array                  $properties
     */
    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        AttachmentManager $attachmentManager,
        $userEntityName,
        array $properties
    ) {
        $this->tokenAccessor = $tokenAccessor;

        parent::__construct($attachmentManager, $userEntityName, $properties);
    }

    /**
     * {@inheritdoc}
     */
    protected function searchIds($search, $firstResult, $maxResults)
    {
        $userIds = parent::searchIds($search, $firstResult, $maxResults + 1);

        $excludedKey = null;
        $currentUserId = $this->tokenAccessor->getUserId();
        if ($currentUserId) {
            $excludedKey = array_search($currentUserId, $userIds);
        }

        if (false !== $excludedKey) {
            unset($userIds[$excludedKey]);
            $userIds = array_values($userIds);
        } else {
            $userIds = array_slice($userIds, 0, $maxResults);
        }

        return $userIds;
    }
}
