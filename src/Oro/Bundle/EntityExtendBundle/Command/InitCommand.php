<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\SchemaGenerator;

class InitCommand extends ContainerAwareCommand
{
    /**
     * Console command configuration
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-extend:init')
            ->setDescription('Find description about custom entities and fields');
    }

    /**
     * Runs command
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @throws \InvalidArgumentException
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->getDescription());

        /** @var ConfigManager $configManager */
        $configManager = $this->getContainer()->get('oro_entity_config.config_manager');

        /** @var SchemaGenerator $schemaGenerator */
        $schemaGenerator = $this->getContainer()->get('oro_entity_extend.tools.schema_generator');

        $configs = [];

        /** @var Kernel $kernel */
        $kernel = $this->getContainer()->get('kernel');
        foreach ($kernel->getBundles() as $bundle) {
            $path = $bundle->getPath() . '/Resources/config/entity_extend.yml';
            if (is_file($path)) {
                $config = Yaml::parse(realpath($path));
                foreach ($config as $className => $entityOptions) {
                    if (in_array($className, $configs)) {
                        $configs[$className] = array_merge($configs[$className], $entityOptions);
                    } else {
                        $configs[$className] = $entityOptions;
                    }
                }
            }
        }

        $schemaGenerator->parseConfigs($configs);

        $output->writeln('Done');

        $this->getContainer()->get('oro_entity_extend.tools.dumper')->clear();

        $configManager->clearConfigurableCache();
    }
}
