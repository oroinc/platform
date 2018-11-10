<?php

namespace Oro\Bundle\AttachmentBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The API manager for Attachment entity.
 */
class AttachmentApiEntityManager extends ApiEntityManager
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var AttachmentManager */
    protected $attachmentManager;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var array|null */
    protected $attachmentTargets;

    /**
     * @param string                        $class
     * @param ObjectManager                 $om
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param AttachmentManager             $attachmentManager
     * @param DoctrineHelper                $doctrineHelper
     */
    public function __construct(
        $class,
        ObjectManager $om,
        AuthorizationCheckerInterface $authorizationChecker,
        AttachmentManager $attachmentManager,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct($class, $om);
        $this->authorizationChecker = $authorizationChecker;
        $this->attachmentManager = $attachmentManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param Attachment $entity
     *
     * @throws AccessDeniedException
     */
    protected function checkFoundEntity($entity)
    {
        parent::checkFoundEntity($entity);

        $attachmentTarget = $entity->getTarget();
        if ($attachmentTarget && !$this->authorizationChecker->isGranted('VIEW', $attachmentTarget)) {
            throw new AccessDeniedException();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getSerializationConfig()
    {
        $config = [
            'fields'         => [
                'owner'        => ['fields' => 'id'],
                'organization' => ['fields' => 'name'],
                'file'         => ['fields' => 'id']
            ],
            'post_serialize' => function (array $result) {
                return $this->postSerializeAttachment($result);
            }
        ];

        $attachmentTargets = $this->getAttachmentTargets();
        foreach ($attachmentTargets as $targetClass => $fieldName) {
            $config['fields'][$fieldName] = [
                'fields' => $this->doctrineHelper->getSingleEntityIdentifierFieldName($targetClass)
            ];
        }

        return $config;
    }

    /**
     * @param array $result
     *
     * @return array
     */
    protected function postSerializeAttachment(array $result): array
    {
        if (!empty($result['file'])) {
            $result['file'] = $this->attachmentManager->getFileRestApiUrl(
                $result['file']['id'],
                $this->class,
                $result['id']
            );
        }

        // move all attachment association fields into 'target' field
        $result['target']  = null;
        $attachmentTargets = $this->getAttachmentTargets();
        foreach ($attachmentTargets as $targetClass => $fieldName) {
            if (null !== $result[$fieldName]) {
                $targetIdFieldName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($targetClass);
                $result['target'] = [
                    'entity' => $targetClass,
                    'id'     => $result[$fieldName][$targetIdFieldName]
                ];
            }
        }
        foreach ($attachmentTargets as $targetClass => $fieldName) {
            unset($result[$fieldName]);
        }

        return $result;
    }

    /**
     * Returns the list of fields responsible to store attachment associations
     *
     * @return array [target_entity_class => field_name]
     */
    protected function getAttachmentTargets()
    {
        if (null === $this->attachmentTargets) {
            $this->attachmentTargets = $this->attachmentManager->getAttachmentTargets();
        }

        return $this->attachmentTargets;
    }
}
