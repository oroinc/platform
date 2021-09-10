<?php
declare(strict_types=1);

namespace Oro\Bundle\ActionBundle\Command;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;
use Oro\Bundle\ActionBundle\Configuration\ConfigurationValidatorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Validates action configuration.
 */
class ValidateActionConfigurationCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:action:configuration:validate';

    private ConfigurationProviderInterface $operationsProvider;
    private ConfigurationValidatorInterface $configurationValidator;

    public function __construct(
        ConfigurationProviderInterface $operationsProvider,
        ConfigurationValidatorInterface $configurationValidator
    ) {
        parent::__construct();

        $this->operationsProvider = $operationsProvider;
        $this->configurationValidator = $configurationValidator;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDescription('Validates action configuration.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command validates action configuration and displays the encountered errors.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Load actions ...');

        $configuration = $this->operationsProvider->getConfiguration();
        if ($configuration) {
            $errors = new ArrayCollection();
            $this->configurationValidator->validate($configuration, $errors);

            $output->writeln(sprintf('Found %d action(s) with %d error(s)', count($configuration), count($errors)));
            foreach ($errors as $error) {
                $output->writeln($error);
            }
        } else {
            $output->writeln('No actions found.');
        }

        return 0;
    }
}
