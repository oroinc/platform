<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

class OroUserBundle implements Migration, AttachmentExtensionAwareInterface, ContainerAwareInterface
{
    /** @var AttachmentExtension */
    protected $attachmentExtension;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        //add attachment extend field
        self::addAvatarToUser($schema, $this->attachmentExtension);

        //save old avatars to new place
        $em = $this->container->get('doctrine.orm.entity_manager');
        $query = 'SELECT id, image, createdAt FROM oro_user WHERE image != ""';
        $userImages = $em->getConnection()->executeQuery($query)->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($userImages)) {
            foreach ($userImages as $userData) {
                $filePath = $this->getUploadFileName($userData);
                $this->container->get('oro_attachment.manager')->copyLocalFileToStorage($filePath, $userData['image']);

                $file = new File($filePath);
                $attachmentEntity = new Attachment();
                $attachmentEntity->setExtension($file->guessExtension());
                $attachmentEntity->setOriginalFilename($file->getFileName());
                $attachmentEntity->setMimeType($file->getMimeType());
                $attachmentEntity->setFileSize($file->getSize());
                $attachmentEntity->setFilename($userData['image']);

                $em->persist($attachmentEntity);
                $em->flush();

                $query = sprintf(
                    'UPDATE oro_user set avatar_id = %d WHERE id = %d',
                    $attachmentEntity->getId(),
                    $userData['id']
                );
                $em->getConnection()->executeQuery($query)
                    ->execute();
                unlink($filePath);
            }
        }

        //delete old avatars field
        $schema->getTable('oro_user')->dropColumn('image');
    }

    /**
     * @param Schema $schema
     * @param AttachmentExtension $attachmentExtension
     */
    public static function addAvatarToUser(Schema $schema, AttachmentExtension $attachmentExtension)
    {
        $attachmentExtension->addAttachmentRelation($schema, 'oro_user', 'avatar', 'attachmentImage', 2, 58, 58);
    }

    protected function getUploadFileName($userData)
    {
        $ds = DIRECTORY_SEPARATOR;
        $dateObject = new \DateTime($userData['createdAt']);
        $suffix = $dateObject->format('Y-m');
        $path = $this->container->getParameter('kernel.root_dir')
            . '/../web/uploads' . $ds . 'users' . $ds . $suffix . $ds . $userData['image'];

        return realpath($path);
    }
}
