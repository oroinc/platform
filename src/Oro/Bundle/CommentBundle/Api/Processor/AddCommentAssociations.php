<?php

namespace Oro\Bundle\CommentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\CommentBundle\Api\CommentAssociationProvider;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds associations with the comment entity to all entities that can have comments.
 */
class AddCommentAssociations implements ProcessorInterface
{
    private const COMMENTS_ASSOCIATION_NAME = 'comments';

    private CommentAssociationProvider $commentAssociationProvider;

    public function __construct(CommentAssociationProvider $commentAssociationProvider)
    {
        $this->commentAssociationProvider = $commentAssociationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $entityClass = $context->getClassName();

        $commentAssociationName = $this->commentAssociationProvider->getCommentAssociationName(
            $entityClass,
            $context->getVersion(),
            $context->getRequestType()
        );
        if ($commentAssociationName) {
            $this->addCommentsAssociation(
                $context->getResult(),
                $entityClass,
                self::COMMENTS_ASSOCIATION_NAME,
                $commentAssociationName,
                $context->getTargetAction()
            );
        }
    }

    private function addCommentsAssociation(
        EntityDefinitionConfig $definition,
        string $entityClass,
        string $associationName,
        string $commentAssociationName,
        ?string $targetAction
    ): void {
        if ($definition->hasField($associationName)) {
            $dataType = $definition->getField($associationName)->getDataType();
            if ($dataType && 'unidirectionalAssociation:' . $commentAssociationName !== $dataType) {
                throw new \RuntimeException(sprintf(
                    'The association "%s" cannot be added to "%s"'
                    . ' because an association with this name already exists.',
                    $associationName,
                    $entityClass
                ));
            }
        }

        $association = $definition->getOrAddField($associationName);
        $association->setTargetClass(Comment::class);
        $association->setDataType('unidirectionalAssociation:' . $commentAssociationName);
        if (ApiAction::UPDATE === $targetAction) {
            $association->setFormOption('mapped', false);
        }
    }
}
