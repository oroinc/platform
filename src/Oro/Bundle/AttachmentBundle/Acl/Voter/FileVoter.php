<?php

namespace Oro\Bundle\AttachmentBundle\Acl\Voter;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\AttachmentBundle\Acl\FileAccessControlChecker;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileApplicationsProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Defines whether the current user is allowed to view File.
 */
class FileVoter extends AbstractEntityVoter
{
    /** @var array */
    protected $supportedAttributes = [BasicPermission::VIEW, BasicPermission::EDIT, BasicPermission::DELETE];

    /** @var CurrentApplicationProviderInterface */
    private $currentApplicationProvider;

    /** @var FileApplicationsProvider */
    private $fileApplicationsProvider;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var FileAccessControlChecker */
    private $fileAccessControlChecker;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        FileAccessControlChecker $fileAccessControlChecker,
        FileApplicationsProvider $fileApplicationsProvider,
        CurrentApplicationProviderInterface $currentApplicationProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor
    ) {
        parent::__construct($doctrineHelper);

        $this->fileAccessControlChecker = $fileAccessControlChecker;
        $this->fileApplicationsProvider = $fileApplicationsProvider;
        $this->currentApplicationProvider = $currentApplicationProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        /** @var File $file */
        $file = $this->doctrineHelper->getEntity($class, $identifier);
        if (!$file) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // Checks if file is covered by ACL.
        if (!$this->fileAccessControlChecker->isCoveredByAcl($file)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        $allowedApplications = $this->fileApplicationsProvider->getFileApplications($file);
        if (!$this->currentApplicationProvider->isApplicationsValid($allowedApplications)) {
            return VoterInterface::ACCESS_DENIED;
        }

        $parentEntity = $this->getParentEntity($file);
        if (!$parentEntity) {
            return VoterInterface::ACCESS_DENIED;
        }

        if (BasicPermission::VIEW === $attribute && $this->tokenAccessor->getUser() === $parentEntity) {
            // Allows to view own avatar for those who do not have permission to view User entity.
            return VoterInterface::ACCESS_GRANTED;
        }

        return $this->authorizationChecker->isGranted($attribute, $parentEntity)
            ? VoterInterface::ACCESS_GRANTED
            : VoterInterface::ACCESS_DENIED;
    }

    /**
     * @param File $file
     *
     * @return object|null
     */
    private function getParentEntity(File $file)
    {
        $parentEntityClass = $file->getParentEntityClass();
        $parentEntityId = $file->getParentEntityId();
        if (!$parentEntityClass || !$parentEntityId) {
            return null;
        }

        return $this->doctrineHelper->getEntity($parentEntityClass, $parentEntityId);
    }
}
