<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Command;

use Oro\Bundle\EntityConfigBundle\EntityConfig\ConfigurationHandler;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Dumps the reference structure for Resources/config/oro/entity.yml.
 */
class OroEntityConfigDumpReferenceCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:entity:config:validation:dump-reference';

    private ConfigurationHandler $configurationHandler;

    public function __construct(ConfigurationHandler $configurationHandler)
    {
        $this->configurationHandler = $configurationHandler;
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDescription('List of entity config validation with description')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command dumps the reference structure
for view list of entity config validation with description.

  <info>php %command.full_name%</info>

HELP
            );
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new SymfonyStyle($input, $output);

        $dumper = new YamlReferenceDumper();

        $configType = ConfigurationHandler::CONFIG_ENTITY_TYPE;
        $configAttrNames = $this->configurationHandler->getAvailableScopes($configType);

        $output->writeln('# @Config');
        $output->writeln('# This annotation is used to configure default values for configurable entity classes.');

        foreach ($configAttrNames as $name) {
            $output->writeln($dumper->dump($this->configurationHandler->getConfiguration($configType, $name)));
        }

        $configType = ConfigurationHandler::CONFIG_FIELD_TYPE;
        $configAttrNames = $this->configurationHandler->getAvailableScopes($configType);

        $output->writeln('# @ConfigField');
        $output->writeln('# This annotation is used to configure default' .
            'values for properties of configurable entity classes.');

        foreach ($configAttrNames as $name) {
            $config = $this->configurationHandler->getConfiguration($configType, $name);
            if ($config) {
                $output->writeln($dumper->dump($config));
            }
        }

        return 0;
    }
}
