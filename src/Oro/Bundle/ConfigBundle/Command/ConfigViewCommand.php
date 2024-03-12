<?php

declare(strict_types=1);

namespace Oro\Bundle\ConfigBundle\Command;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\Tree\FieldNodeDefinition;
use Oro\Bundle\ConfigBundle\Config\Tree\GroupNodeDefinition;
use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;
use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Views a configuration value in the global scope.
 */
class ConfigViewCommand extends Command
{
    protected static $defaultName = 'oro:config:view';

    private ConfigManager $configManager;
    private SystemConfigurationFormProvider $formProvider;

    public function __construct(
        ConfigManager $configManager,
        SystemConfigurationFormProvider $formProvider,
    ) {
        $this->configManager = $configManager;
        $this->formProvider = $formProvider;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Config parameter name')
            ->setDescription('Views a configuration value in the global scope.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command views a configuration value in the global scope.

  <info>php %command.full_name% <name></info>

For example, to view the back-office and storefront URLs of an OroCommerce instance respectively:

  <info>php %command.full_name% oro_ui.application_url</info>
  <info>php %command.full_name% oro_website.url</info>
  <info>php %command.full_name% oro_website.secure_url</info>

HELP
            )
        ;
    }

    /**
     * Find a field node by name from the config tree
     *
     * @param GroupNodeDefinition $node
     * @param string $fieldName
     * @return ?FieldNodeDefinition null if no matching node was found
     */
    protected function findFieldNode(GroupNodeDefinition $node, string $fieldName): ?FieldNodeDefinition
    {
        foreach ($node as $child) {
            if ($child instanceof GroupNodeDefinition) {
                $result = $this->findFieldNode($child, $fieldName);
                if ($result !== null) {
                    return $result;
                }
            } elseif ($child instanceof FieldNodeDefinition) {
                if ($child->getName() === $fieldName) {
                    return $child;
                }
            }
        }

        return null;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);
        $configManager = $this->configManager;
        $fieldName = $input->getArgument('name');

        $configTree = $this->formProvider->getTree();
        $configField = $this->findFieldNode($configTree, $fieldName);
        if ($configField !== null
            && $configField->getType() === OroEncodedPlaceholderPasswordType::class
        ) {
            $symfonyStyle->error("Encrypted value");
            return Command::INVALID;
        }

        $value = $configManager->get($fieldName);
        if (is_null($value)) {
            $symfonyStyle->error("Value could not be retrieved");
            return Command::FAILURE;
        }
        if (is_array($value) || is_object($value) || is_bool($value)) {
            $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        if (!is_scalar($value)) {
            $symfonyStyle->error("Value cannot be displayed");
            return Command::FAILURE;
        }

        $output->writeln($value);
        return Command::SUCCESS;
    }
}
