<?php

namespace Oro\Bundle\FeatureToggleBundle\Command;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtension;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Dumps features configured in "Resources/config/oro/features.yml".
 */
class ConfigDebugCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:feature-toggle:config:debug';

    private ConfigurationProvider $configurationProvider;
    private ConfigurationExtension $configurationExtension;
    private TranslatorInterface $translator;

    public function __construct(
        ConfigurationProvider $configurationProvider,
        ConfigurationExtension $configurationExtension,
        TranslatorInterface $translator
    ) {
        parent::__construct();
        $this->configurationProvider = $configurationProvider;
        $this->configurationExtension = $configurationExtension;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument('feature', InputArgument::OPTIONAL, 'The name of a feature')
            ->setDescription('Dumps features configured in Resources/config/oro/features.yml.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command dumps configuration of features.

  <info>php %command.full_name%</info>
  <info>php %command.full_name% <feature></info>

HELP
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $featuresConfig = $this->configurationProvider->getFeaturesConfiguration();
        /** @var string|null $feature */
        $feature = $input->getArgument('feature');
        if (!$feature) {
            if ($featuresConfig) {
                $this->dumpConfig($io, $this->getConfigs($featuresConfig, $io->isVerbose()));
            }
        } elseif (isset($featuresConfig[$feature])) {
            $this->dumpConfig(
                $io,
                $this->configurationExtension->completeConfiguration(
                    [$feature => $this->getConfig($featuresConfig[$feature], true)]
                )
            );
        } else {
            throw new InvalidArgumentException('Unknown feature.');
        }

        return 0;
    }

    private function getConfigs(array $configs, bool $verbose): array
    {
        $result = [];
        ksort($configs);
        foreach ($configs as $featureName => $config) {
            $result[$featureName] = $this->getConfig($config, $verbose);
        }
        if ($verbose) {
            $result = $this->configurationExtension->completeConfiguration($result);
        }

        return $result;
    }

    private function getConfig(array $config, bool $verbose): array
    {
        $result = [
            'label' => $this->translator->trans($config['label'])
        ];
        if ($verbose) {
            if (!empty($config['description'])) {
                $result['description'] = $this->translator->trans($config['description']);
            }
            $this->addConfigOptionIsExists($result, $config, 'toggle');
            $this->addConfigOptionIsExists($result, $config, 'strategy');
            $this->addConfigOptionIsExists($result, $config, 'allow_if_all_abstain');
            $this->addConfigOptionIsExists($result, $config, 'allow_if_equal_granted_denied');
            $otherOptions = array_diff_key($config, $result);
            foreach ($otherOptions as $key => $value) {
                if (\is_array($value) && empty($value)) {
                    unset($otherOptions[$key]);
                }
            }
            ksort($otherOptions);
            $result = array_merge($result, $otherOptions);
        }

        return $result;
    }

    private function addConfigOptionIsExists(array &$result, array $config, string $optionName): void
    {
        if (\array_key_exists($optionName, $config)) {
            $result[$optionName] = $config[$optionName];
        }
    }

    private function dumpConfig(OutputInterface $output, array $config): void
    {
        $output->write(Yaml::dump($config, 100, 4, Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE));
    }
}
