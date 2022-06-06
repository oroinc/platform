<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Removes unused localized fallback values.
 */
class CleanupUnusedLocalizedFallbackValuesCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:localization:localized-fallback-values:cleanup-unused';

    /** @var string */
    protected static $defaultDescription = 'Removes unused localized fallback values.';

    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setHelp(
            <<<'HELP'
The <info>%command.name%</info> command cleans up unused data in `oro_fallback_localization_val` database table.

You can execute this command in non-interactive mode to skip the interactive confirmation request:

    <info>%command.full_name% --no-interaction</info>

    Or

    <info>%command.full_name% -n</info>
HELP
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->confirmCleanup($input, $io)) {
            return self::SUCCESS;
        }

        $entityManager = $this->managerRegistry->getManagerForClass(LocalizedFallbackValue::class);
        $repository = $entityManager->getRepository(LocalizedFallbackValue::class);

        $classesMetadata = $entityManager->getMetadataFactory()->getAllMetadata();

        $numberOfRemovedValues = 0;
        $unusedIds = $repository->getUnusedLocalizedFallbackValueIds($classesMetadata);
        while ($unusedIds) {
            $numberOfRemovedValues += $repository->deleteByIds($unusedIds);

            $unusedIds = $repository->getUnusedLocalizedFallbackValueIds($classesMetadata);
        }

        $io->success([
            'Removing unused localized fallback values completed.',
            sprintf(
                'Deleted: %d records.',
                $numberOfRemovedValues
            )
        ]);

        return self::SUCCESS;
    }

    protected function confirmCleanup(InputInterface $input, SymfonyStyle $io): bool
    {
        if (!$input->isInteractive()) {
            $confirmation = true;
        } else {
            $io->caution('You are about to remove unused localized fallback values.');
            $io->warning([
                'Because of potentially heavy load during the command execution',
                'Database backup is highly recommended before executing this command.',
            ]);

            $confirmation = $io->confirm('Are you sure you wish to continue?');
        }

        if (!$confirmation) {
            $io->warning('Cleanup cancelled!');
        }

        return $confirmation;
    }
}
