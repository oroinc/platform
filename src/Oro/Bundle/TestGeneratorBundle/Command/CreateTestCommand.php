<?php
declare(strict_types=1);

namespace Oro\Bundle\TestGeneratorBundle\Command;

use Oro\Bundle\TestGeneratorBundle\Generator\AbstractTestGenerator;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates a test stub.
 */
class CreateTestCommand extends Command
{
    protected static $defaultName = 'oro:generate:test';

    private const PHP_VERSION = 7.4;

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument('class', InputArgument::REQUIRED, 'FQCN or path to php file')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'Test type (unit, entity, functional)'
            )
            ->addArgument('php_version', InputArgument::OPTIONAL, 'PHP version')
            ->setDescription('Creates a test stub.')
            ->setHelp(
            // @codingStandardsIgnoreStart
            <<<'HELP'
The <info>%command.name%</info> command creates a <comment>unit</comment>, <comment>entity</comment> or <comment>functional</comment> test stub
for a specified class, entity or controller.

  <info>php %command.full_name% <fqcn-or-filepath> <type></info>

A test stub can be generated for a specific PHP version provided in the third argument:

  <info>php %command.full_name% <fqcn-or-filepath> <type> <php-version></info>

HELP
            // @codingStandardsIgnoreEnd
            )
            ->addUsage('<fqcn-or-filepath> unit')
            ->addUsage('<fqcn-or-filepath> entity')
            ->addUsage('<fqcn-or-filepath> functional')
            ->addUsage('<fqcn-or-filepath> <type> <php-version>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('type');
        if (!in_array($type, ['unit', 'entity', 'functional'], true)) {
            throw new \InvalidArgumentException(
                sprintf('Type "%s" is not known. Supported types are: unit, entity, functional', $type)
            );
        }
        $phpVersion = (float)$input->getArgument('php_version') ?: self::PHP_VERSION;
        /** @var AbstractTestGenerator $generator */
        $generator = $this->container->get('oro_test_generator.generator.test.'.$type);
        $generator->setPhpVersion($phpVersion);
        $class = $input->getArgument('class');
        if (strpos($class, '\\') === false) {
            $class = str_replace('.php', '', $class);
            $class = str_replace('/', '\\', substr($class, strripos($class, '/src/') + 5));
        }
        if (class_exists($class)) {
            $generator->generate($class);
            $message = '<info>Test was generated successful</info>';
        } else {
            $message = '<error>Class not found. Please, make sure that class name is'
            . ' correct and package in which the class is declared is in the composer "require" section of current'
            . ' application</error>';
        }
        $output->writeln($message);
    }
}
