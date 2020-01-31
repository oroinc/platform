<?php

namespace Oro\Bundle\AttachmentBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Migration\FilteredAttachmentMigrationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Migrate filtered images folder structure to new one
 */
class MigrateImagesCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'oro:attachment:migrate-directory-structure';

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var FilteredAttachmentMigrationService
     */
    private $migrationService;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @param ManagerRegistry $registry
     * @param FilteredAttachmentMigrationService $migrationService
     * @param string $prefix
     */
    public function __construct(
        ManagerRegistry $registry,
        FilteredAttachmentMigrationService $migrationService,
        string $prefix
    ) {
        $this->registry = $registry;
        $this->migrationService = $migrationService;
        $this->prefix = $prefix;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Migrate filtered attachments to new directory structure');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Starting attachments migration');
        $manager = $this->registry->getManagerForClass(File::class);
        $this->migrationService->setManager($manager);
        $this->migrationService->migrate($this->prefix, $this->prefix);
        $output->writeln('Attachments migration finished');
    }
}
