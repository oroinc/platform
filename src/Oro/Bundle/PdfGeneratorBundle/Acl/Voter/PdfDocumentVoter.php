<?php

namespace Oro\Bundle\PdfGeneratorBundle\Acl\Voter;

use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * ACL voter for PDF documents.
 * This voter checks if the user has permission to view the source entity of the PDF document.
 */
class PdfDocumentVoter extends AbstractEntityVoter implements ServiceSubscriberInterface
{
    protected $supportedAttributes = [BasicPermission::VIEW];

    private ContainerInterface $container;

    private ?TokenInterface $token = null;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_security.authorization_checker' => AuthorizationCheckerInterface::class,
        ];
    }

    #[\Override]
    public function vote(TokenInterface $token, $object, array $attributes): int
    {
        $this->token = $token;
        try {
            return parent::vote($token, $object, $attributes);
        } finally {
            $this->token = null;
        }
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

        /** @var AuthorizationCheckerInterface $authorizationChecker */
        $authorizationChecker = $this->container->get('oro_security.authorization_checker');

        return $authorizationChecker->isGranted($attribute, $sourceEntity)
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
