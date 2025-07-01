<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserInterface;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions\ResourceDocParserProvider;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Util\InheritDocUtil;
use Oro\Bundle\AttachmentBundle\Api\MultiFileAssociationProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds human-readable descriptions for multi files and multi images associations.
 */
class AddMultiFileAssociationDescriptions implements ProcessorInterface
{
    public function __construct(
        private readonly MultiFileAssociationProvider $multiFileAssociationProvider,
        private readonly ResourceDocParserProvider $resourceDocParserProvider
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $targetAction = $context->getTargetAction();
        if (!$targetAction || ApiAction::OPTIONS === $targetAction) {
            return;
        }

        $associationName = $context->getAssociationName();
        if ($associationName) {
            return;
        }

        $requestType = $context->getRequestType();

        $multiFileAssociationNames = $this->multiFileAssociationProvider->getMultiFileAssociationNames(
            $context->getClassName(),
            $context->getVersion(),
            $requestType
        );
        if (!$multiFileAssociationNames) {
            return;
        }

        $definition = $context->getResult();
        $docParser = $this->resourceDocParserProvider->getResourceDocParser($requestType);
        $docParser->registerDocumentationResource('@OroAttachmentBundle/Resources/doc/api/multi_file_association.md');
        foreach ($multiFileAssociationNames as $associationName) {
            $associationDefinition = $definition->getField($associationName);
            if (null !== $associationDefinition) {
                $associationDocumentationTemplate = $this->getAssociationDocumentationTemplate(
                    $docParser,
                    '%multi_file_target_entity%',
                    '%multi_file_association%',
                    $targetAction
                );
                $associationDefinition->setDescription(InheritDocUtil::replaceInheritDoc(
                    $associationDocumentationTemplate,
                    $definition->findFieldByPath($associationName, true)?->getDescription()
                ));
            }
        }
    }

    private function getAssociationDocumentationTemplate(
        ResourceDocParserInterface $docParser,
        string $className,
        string $fieldName,
        string $targetAction
    ): ?string {
        return $docParser->getFieldDocumentation($className, $fieldName, $targetAction)
            ?: $docParser->getFieldDocumentation($className, $fieldName);
    }
}
