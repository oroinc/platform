<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\AttachmentBundle\Api\AttachmentAssociationProvider;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds associations with the attachment entity to all entities that can have attachments.
 */
class AddAttachmentAssociations implements ProcessorInterface
{
    private const ATTACHMENTS_ASSOCIATION_NAME = 'attachments';

    private AttachmentAssociationProvider $attachmentAssociationProvider;

    public function __construct(AttachmentAssociationProvider $attachmentAssociationProvider)
    {
        $this->attachmentAssociationProvider = $attachmentAssociationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $entityClass = $context->getClassName();

        $attachmentAssociationName = $this->attachmentAssociationProvider->getAttachmentAssociationName(
            $entityClass,
            $context->getVersion(),
            $context->getRequestType()
        );
        if ($attachmentAssociationName) {
            $this->addAttachmentsAssociation(
                $context->getResult(),
                $entityClass,
                self::ATTACHMENTS_ASSOCIATION_NAME,
                $attachmentAssociationName,
                $context->getTargetAction()
            );
        }
    }

    private function addAttachmentsAssociation(
        EntityDefinitionConfig $definition,
        string $entityClass,
        string $associationName,
        string $attachmentAssociationName,
        ?string $targetAction
    ): void {
        if ($definition->hasField($associationName)) {
            $dataType = $definition->getField($associationName)->getDataType();
            if ($dataType && 'unidirectionalAssociation:' . $attachmentAssociationName !== $dataType) {
                throw new \RuntimeException(sprintf(
                    'The association "%s" cannot be added to "%s"'
                    . ' because an association with this name already exists.',
                    $associationName,
                    $entityClass
                ));
            }
        }

        $association = $definition->getOrAddField($associationName);
        $association->setTargetClass(Attachment::class);
        $association->setDataType('unidirectionalAssociation:' . $attachmentAssociationName);
        if (ApiAction::UPDATE === $targetAction) {
            $association->setFormOption('mapped', false);
        }
    }
}
