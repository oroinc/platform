<?php

namespace Oro\Bundle\CommentBundle\Tools;

use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides a method to check whether comments are enabled for a specific entity type.
 */
class CommentAssociationHelper
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Checks whether comments are enabled for a given entity type.
     *
     * @param string $entityClass The target entity class
     * @param bool $accessible    Whether an association with the target entity should be checked
     *                            to be ready to use in a business logic.
     *                            It means that the association should exist and should not be marked as deleted.
     *
     * @return bool
     */
    public function isCommentAssociationEnabled(string $entityClass, bool $accessible = true): bool
    {
        if (!$this->configManager->hasConfig($entityClass)) {
            return false;
        }
        if (!$this->configManager->getEntityConfig('comment', $entityClass)->is('enabled')) {
            return false;
        }

        return
            !$accessible
            || $this->isCommentAssociationAccessible($entityClass);
    }

    /**
     * Check if an association between a given entity type and comments is ready to be used in a business logic.
     * It means that the association should exist and should not be marked as deleted.
     */
    private function isCommentAssociationAccessible(string $entityClass): bool
    {
        $associationName = ExtendHelper::buildAssociationName($entityClass);
        if (!$this->configManager->hasConfig(Comment::class, $associationName)) {
            return false;
        }

        return ExtendHelper::isFieldAccessible(
            $this->configManager->getFieldConfig('extend', Comment::class, $associationName)
        );
    }
}
