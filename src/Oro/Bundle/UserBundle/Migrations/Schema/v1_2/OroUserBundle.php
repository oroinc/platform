<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

class OroUserBundle implements
    Migration,
    AttachmentExtensionAwareInterface,
    ContainerAwareInterface,
    ConnectionAwareInterface,
    LoggerAwareInterface
{
    use AttachmentExtensionAwareTrait;
    use ContainerAwareTrait;
    use ConnectionAwareTrait;
    use LoggerAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        //add attachment extend field
        $this->attachmentExtension->addImageRelation($schema, 'oro_user', 'avatar', [], 2, 58, 58);
        $this->addOwnerToAttachmentFileTable($schema);

        //save old avatars to new place
        $query = "SELECT id, image, createdAt FROM oro_user WHERE image != ''";
        $userImages = $this->connection->executeQuery($query)->fetchAllAssociative(\PDO::FETCH_ASSOC);

        if (!empty($userImages)) {
            $maxId = (int)$this->connection
                ->executeQuery('SELECT MAX(id) FROM oro_attachment_file;')
                ->fetchOne();
            foreach ($userImages as $userData) {
                $filePath = $this->getUploadFileName($userData);
                // file doesn't exists or not readable
                if (false === $filePath || !is_readable($filePath)) {
                    $this->logger->error(
                        sprintf('There\'s no image %s for user %d exists.', $userData['image'], $userData['id'])
                    );
                    continue;
                }

                try {
                    $this->container->get('oro_attachment.file_manager')
                        ->writeFileToStorage($filePath, $userData['image']);
                } catch (\Exception $e) {
                    $this->logger->error(sprintf('File copy error: %s', $e->getMessage()));
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
                    $file->getFilename(),
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

    private function addOwnerToAttachmentFileTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_attachment_file');
        $table->addColumn('owner_user_id', 'integer', ['notnull' => false]);
        $table->addIndex(['owner_user_id'], 'IDX_6E4CD01B9EB185F9', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    private function getUploadFileName(array $userData): string|false
    {
        $ds = DIRECTORY_SEPARATOR;
        $dateObject = new \DateTime($userData['createdAt']);
        $suffix = $dateObject->format('Y-m');
        $path = $this->container->getParameter('kernel.project_dir')
            . '/public/uploads' . $ds . 'users' . $ds . $suffix . $ds . $userData['image'];

        return realpath($path);
    }
}
