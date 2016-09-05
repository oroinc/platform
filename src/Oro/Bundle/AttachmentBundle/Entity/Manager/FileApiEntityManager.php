<?php

namespace Oro\Bundle\AttachmentBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Model\FileContentProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class FileApiEntityManager extends ApiEntityManager
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var AttachmentManager */
    protected $attachmentManager;

    /** @var FileManager */
    protected $fileManager;

    /**
     * @param string            $class
     * @param ObjectManager     $om
     * @param SecurityFacade    $securityFacade
     * @param FileManager       $fileManager
     * @param AttachmentManager $attachmentManager
     */
    public function __construct(
        $class,
        ObjectManager $om,
        SecurityFacade $securityFacade,
        FileManager $fileManager,
        AttachmentManager $attachmentManager
    ) {
        parent::__construct($class, $om);
        $this->securityFacade    = $securityFacade;
        $this->attachmentManager = $attachmentManager;
        $this->fileManager = $fileManager;
    }

    /**
     * {@inheritdoc}
     */
    public function serializeOne($id)
    {
        list($fileId, $ownerEntityClass, $ownerEntityId) = $this->attachmentManager->parseFileKey($id);

        if (!$this->securityFacade->isGranted('VIEW', new ObjectIdentity($ownerEntityId, $ownerEntityClass))) {
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
            'post_serialize' => function (array &$result) {
                $this->postSerializeFile($result);
            }
        ];

        return $config;
    }

    /**
     * @param array $result
     */
    protected function postSerializeFile(array &$result)
    {
        $result['content'] = new FileContentProvider($result['filename'], $this->fileManager);
    }
}
