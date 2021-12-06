<?php

namespace Oro\Bundle\AttachmentBundle\Entity\Manager;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Model\FileContentProvider;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The API manager for File entity.
 */
class FileApiEntityManager extends ApiEntityManager
{
    protected AuthorizationCheckerInterface $authorizationChecker;

    protected FileManager $fileManager;

    protected UrlGeneratorInterface $urlGenerator;

    public function __construct(
        string $class,
        ObjectManager $om,
        AuthorizationCheckerInterface $authorizationChecker,
        FileManager $fileManager,
        UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct($class, $om);
        $this->authorizationChecker = $authorizationChecker;
        $this->fileManager = $fileManager;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Get url of REST API resource which can be used to get the content of the given file
     *
     * @param int $fileId The id of the File object
     *
     * @return string
     */
    public function getFileRestApiUrl(int $fileId): string
    {
        return $this->urlGenerator->generate('oro_api_get_file', ['id' => $fileId, '_format' => 'binary']);
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
