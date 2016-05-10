<?php

namespace Oro\Bundle\TestGeneratorBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class CreateTestCommand extends ContainerAwareCommand
{
    const NAME = 'oro:test:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create Test')
            ->addArgument('class', InputArgument::REQUIRED)
            ->addArgument('type', InputArgument::REQUIRED);

    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $type = $input->getArgument('type');
        if ($type === 'unit') {
            $generator = $container->get('oro_test_generator.generator.test.unit');
        } elseif ($type === 'entity') {
            $generator = $container->get('oro_test_generator.generator.test.entity');
        } elseif ($type == 'functional') {
            $generator = $container->get('oro_test_generator.generator.test.functional');
        }
        if (isset($generator)) {
            $class = $input->getArgument('class');
            if (strpos($class, '\\') === false) {
                $class = str_replace('.php', '', $class);
                $class = str_replace('/', '\\', substr($class, strripos($class, '/src/') + 5));
            }
            $generator->generate($class);
            $output->writeln('<info>Test was generated successful</info>');
        }
    }
}
