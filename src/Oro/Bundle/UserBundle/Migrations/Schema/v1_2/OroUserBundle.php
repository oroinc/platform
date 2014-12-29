<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;

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
        self::addOwnerToOroFile($schema);

        //save old avatars to new place
        $em         = $this->container->get('doctrine.orm.entity_manager');
        $query      = "SELECT id, image, createdAt FROM oro_user WHERE image != ''";
        $userImages = $em->getConnection()->executeQuery($query)->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($userImages)) {
            $maxId = (int)$em->getConnection()
                ->executeQuery('SELECT MAX(id) FROM oro_attachment_file;')
                ->fetchColumn();
            foreach ($userImages as $userData) {
                $filePath = $this->getUploadFileName($userData);
                // file doesn't exists or not readable
                if (false === $filePath || !is_readable($filePath)) {
                    $this->container->get('logger')
                        ->addAlert(
                            sprintf('There\'s no image %s for user %d exists.', $userData['image'], $userData['id'])
                        );
                    continue;
                }

                try {
                    $this->container->get('oro_attachment.manager')
                        ->copyLocalFileToStorage($filePath, $userData['image']);
                } catch (\Exception $e) {
                    $this->container->get('logger')
                        ->addError(sprintf('File copy error: %s', $e->getMessage()));
                }
                $maxId++;
                $file = new SymfonyFile($filePath);
                $currentDate = new \DateTime();
                $query = sprintf(
                    'INSERT INTO oro_attachment_file
                    (id, filename, extension, mime_type, file_size, original_filename,
                     created_at, updated_at, owner_user_id)
                    values (%s, \'%s\', \'%s\', \'%s\', %s, \'%s\', \'%s\', \'%s\', %s);',
                    $maxId,
                    $file->getFileName(),
                    $file->guessExtension(),
                    $file->getMimeType(),
                    $file->getSize(),
                    $userData['image'],
                    $currentDate->format('Y-m-d'),
                    $currentDate->format('Y-m-d'),
                    $userData['id']
                );
                $queries->addQuery($query);

                $query = sprintf(
                    'UPDATE oro_user set avatar_id = %d WHERE id = %d;',
                    $maxId,
                    $userData['id']
                );

                $queries->addQuery($query);

                unlink($filePath);
            }
        }

        //delete old avatars field
        $schema->getTable('oro_user')->dropColumn('image');
    }

    /**
     * Add owner to table oro_file
     *
     * @param Schema $schema
     */
    public static function addOwnerToOroFile(Schema $schema)
    {
        /** Add user as owner to oro_attachment_file table **/
        $table = $schema->getTable('oro_attachment_file');
        $table->addColumn('owner_user_id', 'integer', ['notnull' => false]);
        $table->addIndex(['owner_user_id'], 'IDX_6E4CD01B9EB185F9', []);

        /** Generate foreign keys for table oro_attachment_file **/
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema              $schema
     * @param AttachmentExtension $attachmentExtension
     */
    public static function addAvatarToUser(Schema $schema, AttachmentExtension $attachmentExtension)
    {
        $attachmentExtension->addImageRelation(
            $schema,
            'oro_user',
            'avatar',
            [],
            2,
            58,
            58
        );
    }

    /**
     * @param array $userData
     *
     * @return string|false
     */
    protected function getUploadFileName(array $userData)
    {
        $ds         = DIRECTORY_SEPARATOR;
        $dateObject = new \DateTime($userData['createdAt']);
        $suffix     = $dateObject->format('Y-m');
        $path       = $this->container->getParameter('kernel.root_dir')
            . '/../web/uploads' . $ds . 'users' . $ds . $suffix . $ds . $userData['image'];

        return realpath($path);
    }
}
