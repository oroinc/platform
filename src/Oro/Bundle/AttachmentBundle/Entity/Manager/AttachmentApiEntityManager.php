<?php

namespace Oro\Bundle\AttachmentBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class AttachmentApiEntityManager extends ApiEntityManager
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var AttachmentManager */
    protected $attachmentManager;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var array|null */
    protected $attachmentTargets;

    /**
     * @param string            $class
     * @param ObjectManager     $om
     * @param SecurityFacade    $securityFacade
     * @param AttachmentManager $attachmentManager
     * @param DoctrineHelper    $doctrineHelper
     */
    public function __construct(
        $class,
        ObjectManager $om,
        SecurityFacade $securityFacade,
        AttachmentManager $attachmentManager,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct($class, $om);
        $this->securityFacade    = $securityFacade;
        $this->attachmentManager = $attachmentManager;
        $this->doctrineHelper    = $doctrineHelper;
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
        if ($attachmentTarget && !$this->securityFacade->isGranted('VIEW', $attachmentTarget)) {
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
            'post_serialize' => function (array &$result) {
                $this->postSerializeAttachment($result);
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
     */
    protected function postSerializeAttachment(array &$result)
    {
        if (!empty($result['file'])) {
            $result['file'] = $this->attachmentManager->getFileRestApiUrl(
                $result['file'],
                $this->class,
                $result['id']
            );
        }

        // move all attachment association fields into 'target' field
        $result['target']  = null;
        $attachmentTargets = $this->getAttachmentTargets();
        foreach ($attachmentTargets as $targetClass => $fieldName) {
            if (null !== $result[$fieldName]) {
                $result['target'] = [
                    'entity' => $targetClass,
                    'id'     => $result[$fieldName]
                ];
            }
        }
        foreach ($attachmentTargets as $targetClass => $fieldName) {
            unset($result[$fieldName]);
        }
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
