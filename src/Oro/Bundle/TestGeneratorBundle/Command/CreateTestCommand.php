<?php

namespace Oro\Bundle\TestGeneratorBundle\Command;

use Oro\Bundle\TestGeneratorBundle\Generator\AbstractTestGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class CreateTestCommand extends ContainerAwareCommand
{
    const NAME = 'oro:generate:test';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create Test')
            ->addArgument(
                'class',
                InputArgument::REQUIRED,
                'Full qualified class name or path to php file'
            )
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'Test type. Supported types are: unit, entity, functional'
            )
            ->addArgument('php_version', InputArgument::OPTIONAL, 'PHP version');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('type');
        if (!in_array($type, ['unit', 'entity', 'functional'], true)) {
            throw new \InvalidArgumentException(
                sprintf('Type "%s" is not known. Supported types are: unit, entity, functional', $type)
            );
        }
        $phpVersion = (float)$input->getArgument('php_version') ?: 5.5;
        /** @var AbstractTestGenerator $generator */
        $generator = $this->getContainer()->get('oro_test_generator.generator.test.'.$type);
        $generator->setPhpVersion($phpVersion);
        $class = $input->getArgument('class');
        if (strpos($class, '\\') === false) {
            $class = str_replace('.php', '', $class);
            $class = str_replace('/', '\\', substr($class, strripos($class, '/src/') + 5));
        }
        $generator->generate($class);
        $output->writeln('<info>Test was generated successful</info>');
    }
}
