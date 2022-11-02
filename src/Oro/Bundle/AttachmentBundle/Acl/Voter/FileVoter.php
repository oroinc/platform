<?php

namespace Oro\Bundle\AttachmentBundle\Acl\Voter;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\AttachmentBundle\Acl\FileAccessControlChecker;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileApplicationsProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Defines whether the current user is allowed to view File.
 */
class FileVoter extends AbstractEntityVoter implements ServiceSubscriberInterface
{
    /** {@inheritDoc} */
    protected $supportedAttributes = [BasicPermission::VIEW, BasicPermission::EDIT, BasicPermission::DELETE];

    private AuthorizationCheckerInterface $authorizationChecker;
    private ContainerInterface $container;

    private ?TokenInterface $token;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AuthorizationCheckerInterface $authorizationChecker,
        ContainerInterface $container
    ) {
        parent::__construct($doctrineHelper);
        $this->authorizationChecker = $authorizationChecker;
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_attachment.acl.file_access_control_checker' => FileAccessControlChecker::class,
            'oro_attachment.provider.file_applications' => FileApplicationsProvider::class,
            'oro_action.provider.current_application' => CurrentApplicationProviderInterface::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $this->token = $token;
        try {
            return parent::vote($token, $object, $attributes);
        } finally {
            $this->token = null;
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        /** @var File $file */
        $file = $this->doctrineHelper->getEntity($class, $identifier);
        if (!$file) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // Checks if file is covered by ACL.
        if (!$this->getFileAccessControlChecker()->isCoveredByAcl($file)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        $allowedApplications = $this->getFileApplicationsProvider()->getFileApplications($file);
        if (!$this->getCurrentApplicationProvider()->isApplicationsValid($allowedApplications)) {
            return VoterInterface::ACCESS_DENIED;
        }

        $parentEntity = $this->getParentEntity($file);
        if (null === $parentEntity) {
            return VoterInterface::ACCESS_DENIED;
        }

        if (BasicPermission::VIEW === $attribute && $this->token->getUser() === $parentEntity) {
            // Allows to view own avatar for those who do not have permission to view User entity.
            return VoterInterface::ACCESS_GRANTED;
        }

        return $this->authorizationChecker->isGranted($attribute, $parentEntity)
            ? VoterInterface::ACCESS_GRANTED
            : VoterInterface::ACCESS_DENIED;
    }

    private function getParentEntity(File $file): ?object
    {
        $parentEntityClass = $file->getParentEntityClass();
        if (!$parentEntityClass) {
            return null;
        }
        $parentEntityId = $file->getParentEntityId();
        if (!$parentEntityId) {
            return null;
        }

        return $this->doctrineHelper->getEntity($parentEntityClass, $parentEntityId);
    }

    private function getFileAccessControlChecker(): FileAccessControlChecker
    {
        return $this->container->get('oro_attachment.acl.file_access_control_checker');
    }

    private function getFileApplicationsProvider(): FileApplicationsProvider
    {
        return $this->container->get('oro_attachment.provider.file_applications');
    }

    private function getCurrentApplicationProvider(): CurrentApplicationProviderInterface
    {
        return $this->container->get('oro_action.provider.current_application');
    }
}
