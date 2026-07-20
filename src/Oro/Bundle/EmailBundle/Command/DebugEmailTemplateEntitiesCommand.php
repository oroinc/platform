<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Provider\EmailTemplateEntityProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Lists entity classes by their email template availability status.
 */
#[AsCommand(
    name: 'oro:debug:email:template:entities',
    description: 'Lists entity classes by their email template availability status.'
)]
final class DebugEmailTemplateEntitiesCommand extends Command
{
    public function __construct(
        private readonly EmailTemplateEntityProvider $emailTemplateEntityProvider,
        private readonly ConfigManager $configManager,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption(
                'unavailable',
                null,
                InputOption::VALUE_NONE,
                'Show entity classes that are NOT available when creating email templates.'
            )
            ->addOption(
                'plain',
                null,
                InputOption::VALUE_NONE,
                'Output plain list without formatting. Each line contains one entity class name.'
            )
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command lists entity classes by their email template availability status.

By default it lists all entity classes that have
<comment>email.available_in_template</comment> set to <comment>true</comment> and are available when creating email 
templates:

  <info>php %command.full_name%</info>

Show entity classes that do NOT have <comment>email.available_in_template=true</comment>:

  <info>php %command.full_name% --unavailable</info>

Output as a plain list (useful for piping):

  <info>php %command.full_name% --plain</info>
  <info>php %command.full_name% --unavailable --plain</info>

HELP
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $plain = (bool)$input->getOption('plain');
        $unavailable = (bool)$input->getOption('unavailable');

        $classNames = $unavailable
            ? $this->collectUnavailableEntityClasses()
            : $this->collectAvailableEntityClasses();

        if ($plain) {
            foreach ($classNames as $className) {
                $io->writeln($className);
            }

            return Command::SUCCESS;
        }

        if ($unavailable) {
            $io->section('Entity classes NOT available when creating email templates');
        } else {
            $io->section('Entity classes available when creating email templates');
        }

        if (empty($classNames)) {
            $io->note('No entity classes found.');

            return Command::SUCCESS;
        }

        $io->listing($classNames);

        return Command::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function collectAvailableEntityClasses(): array
    {
        $entities = $this->emailTemplateEntityProvider->getEntities();
        $classNames = array_column($entities, 'name');
        usort($classNames, 'strcasecmp');

        return $classNames;
    }

    /**
     * @return string[]
     */
    private function collectUnavailableEntityClasses(): array
    {
        $availableEntityClasses = $this->collectAvailableEntityClasses();
        $notAvailableEntityClasses = [];
        foreach ($this->configManager->getProvider('email')->getIds() as $configId) {
            $className = $configId->getClassName();
            if (in_array($className, $availableEntityClasses)) {
                continue;
            }

            $notAvailableEntityClasses[] = $className;
        }

        usort($notAvailableEntityClasses, 'strcasecmp');

        return $notAvailableEntityClasses;
    }
}
