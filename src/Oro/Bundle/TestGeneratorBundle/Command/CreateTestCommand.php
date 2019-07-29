<?php

namespace Oro\Bundle\TestGeneratorBundle\Command;

use Oro\Bundle\TestGeneratorBundle\Generator\AbstractTestGenerator;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates test stub
 */
class CreateTestCommand extends Command
{
    protected static $defaultName = 'oro:generate:test';

    private const DEFAUT_PHP_VERSION = 5.5;

    private const SUCCESS_MESSAGE = '<info>Test was generated successful</info>';

    private const CLASS_AUTOLOAD_ERROR_MESSAGE = '<error>Class not found. Please, make sure that class name is'
    . ' correct and package in which the class is declared is in the composer "require" section of current'
    . ' application</error>';

    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
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
        $phpVersion = (float)$input->getArgument('php_version') ?: self::DEFAUT_PHP_VERSION;
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
            $message = self::SUCCESS_MESSAGE;
        } else {
            $message = self::CLASS_AUTOLOAD_ERROR_MESSAGE;
        }
        $output->writeln($message);
    }
}
