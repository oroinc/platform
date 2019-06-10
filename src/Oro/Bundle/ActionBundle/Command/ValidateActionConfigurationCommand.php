<?php

namespace Oro\Bundle\ActionBundle\Command;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;
use Oro\Bundle\ActionBundle\Configuration\ConfigurationValidatorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The CLI command to validate configuration of actions.
 */
class ValidateActionConfigurationCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:action:configuration:validate';

    /** @var ConfigurationProviderInterface */
    private $operationsProvider;

    /** @var ConfigurationValidatorInterface */
    private $configurationValidator;

    /**
     * @param ConfigurationProviderInterface $operationsProvider
     * @param ConfigurationValidatorInterface $configurationValidator
     */
    public function __construct(
        ConfigurationProviderInterface $operationsProvider,
        ConfigurationValidatorInterface $configurationValidator
    ) {
        parent::__construct();

        $this->operationsProvider = $operationsProvider;
        $this->configurationValidator = $configurationValidator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Validate action configuration');
    }

    /**
     * {@inheritdoc}
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
    }
}
