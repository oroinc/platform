<?php

namespace Oro\Bundle\AttachmentBundle\Entity\Manager;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
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

    /** @var FileManager */
    protected $fileManager;

    /**
     * @param string $class
     * @param ObjectManager $om
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param FileManager $fileManager
     */
    public function __construct(
        $class,
        ObjectManager $om,
        AuthorizationCheckerInterface $authorizationChecker,
        FileManager $fileManager
    ) {
        parent::__construct($class, $om);
        $this->authorizationChecker = $authorizationChecker;
        $this->fileManager = $fileManager;
    }

    /**
     * {@inheritdoc}
     */
    public function serializeOne($id)
    {
        if (!$this->authorizationChecker->isGranted('VIEW', new ObjectIdentity($id, File::class))) {
            throw new AccessDeniedException();
        }

        return parent::serializeOne($id);
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

    protected function postSerializeFile(array $result): array
    {
        $result['content'] = new FileContentProvider($result['filename'], $this->fileManager);

        return $result;
    }
}
