<?php

namespace Oro\Bundle\EntityConfigBundle\Command;

use Oro\Bundle\EntityConfigBundle\Tools\AttributeValidator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Validates enum attribute
 *
 * - Numeric option id should be the same as id generated from option name (name=0.025 -> id=0025)
 * - Numeric option translation foreign_key should be the same
 *   as foreign_key generated from content (content=0.25 -> foreign_key=025)
 */
class ValidateAttributeCommand extends Command
{
    protected static $defaultName = 'oro:entity-config:validation:attribute';

    private AttributeValidator $attributeValidator;

    public function __construct(AttributeValidator $attributeValidator)
    {
        $this->attributeValidator = $attributeValidator;
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption(
            'id',
            null,
            InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY,
            'Attributes id to validate'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $results = $this->attributeValidator->validate($input->getOption('id'));
        $output = new SymfonyStyle($input, $output);

        if ($results) {
            foreach ($results as $result) {
                $output->writeln('Attribute: '.$result['class_name'].':'.$result['field_name']);
                $output->newLine();

                if ($outdatedOptions = $result['outdated_options'] ?? []) {
                    $output->writeln('Outdated options');
                    $output->table(['Id', 'Name'], $outdatedOptions);
                }

                if ($outdatedTranslations = $result['outdated_translations'] ?? []) {
                    $output->writeln('Outdated translations');
                    $output->table(['Locale', 'Id', 'Name'], $outdatedTranslations);
                }
            }
        } else {
            $output->info('No violations were found.');
        }

        return self::SUCCESS;
    }
}
