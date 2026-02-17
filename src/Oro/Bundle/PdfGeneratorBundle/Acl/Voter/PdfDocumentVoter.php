<?php

namespace Oro\Bundle\PdfGeneratorBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * ACL voter for PDF documents.
 * This voter checks if the user has permission to view the source entity of the PDF document.
 */
class PdfDocumentVoter extends AbstractEntityVoter
{
    protected $supportedAttributes = [BasicPermission::VIEW];

    public function __construct(
        DoctrineHelper $doctrineHelper,
        private readonly AuthorizationCheckerInterface $authorizationChecker
    ) {
        parent::__construct($doctrineHelper);
    }

    #[\Override]
    protected function getPermissionForAttribute($class, $identifier, $attribute): int
    {
        $pdfDocument = $this->doctrineHelper->getEntity($class, $identifier);
        if ($pdfDocument === null) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        $sourceEntity = $this->getSourceEntity($pdfDocument);
        if ($sourceEntity === null) {
            return VoterInterface::ACCESS_DENIED;
        }

        return $this->authorizationChecker->isGranted($attribute, $sourceEntity)
            ? VoterInterface::ACCESS_GRANTED
            : VoterInterface::ACCESS_DENIED;
    }

    private function getSourceEntity(AbstractPdfDocument $pdfDocument): ?object
    {
        $sourceEntityClass = $pdfDocument->getSourceEntityClass();
        if (!$sourceEntityClass) {
            return null;
        }

        $sourceEntityId = $pdfDocument->getSourceEntityId();
        if (!$sourceEntityId) {
            return null;
        }

        return $this->doctrineHelper->getEntity($sourceEntityClass, $sourceEntityId);
    }
}
