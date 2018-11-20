<?php

namespace Oro\Bundle\AttachmentBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Model\FileContentProvider;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The API manager for File entity.
 */
class FileApiEntityManager extends ApiEntityManager
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var AttachmentManager */
    protected $attachmentManager;

    /** @var FileManager */
    protected $fileManager;

    /**
     * @param string                        $class
     * @param ObjectManager                 $om
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param FileManager                   $fileManager
     * @param AttachmentManager             $attachmentManager
     */
    public function __construct(
        $class,
        ObjectManager $om,
        AuthorizationCheckerInterface $authorizationChecker,
        FileManager $fileManager,
        AttachmentManager $attachmentManager
    ) {
        parent::__construct($class, $om);
        $this->authorizationChecker = $authorizationChecker;
        $this->attachmentManager = $attachmentManager;
        $this->fileManager = $fileManager;
    }

    /**
     * {@inheritdoc}
     */
    public function serializeOne($id)
    {
        list($fileId, $ownerEntityClass, $ownerEntityId) = $this->attachmentManager->parseFileKey($id);

        if (!$this->authorizationChecker->isGranted('VIEW', new ObjectIdentity($ownerEntityId, $ownerEntityClass))) {
            throw new AccessDeniedException();
        }

        return parent::serializeOne($fileId);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSerializationConfig()
    {
        $config = [
            'fields'         => [
                'owner' => ['fields' => 'id']
            ],
            'post_serialize' => function (array $result) {
                return $this->postSerializeFile($result);
            }
        ];

        return $config;
    }

    /**
     * @param array $result
     *
     * @return array
     */
    protected function postSerializeFile(array $result): array
    {
        $result['content'] = new FileContentProvider($result['filename'], $this->fileManager);

        return $result;
    }
}
