<?php

namespace Oro\Bundle\ImportExportBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MoveImportExportFiles implements Migration, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $newFolder =
            $this->container->getParameter('kernel.root_dir') . DIRECTORY_SEPARATOR .
            $this->container->getParameter('importexport.filesystems_storage');

        $oldFolder = $this->container->getParameter('kernel.cache_dir') . '/import_export';

        if (is_dir($oldFolder) && is_dir($newFolder)) {
            $oldFolder .= DIRECTORY_SEPARATOR;
            $newFolder .= DIRECTORY_SEPARATOR;
            foreach (scandir($oldFolder) as $file) {
                if (is_file($oldFolder . $file) && is_readable($oldFolder . $file)) {
                    copy($oldFolder . $file, $newFolder . $file);
                    @unlink($oldFolder . $file);
                }
            }
        }
    }
}
