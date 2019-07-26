<?php

namespace Oro\Bundle\ActionBundle\Command;

use Oro\Bundle\ActionBundle\Helper\DocCommentParser;
use Oro\Component\ConfigExpression\FactoryWithTypesInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract class for get debug info from factories
 */
abstract class AbstractDebugCommand extends Command
{
    /** @var ContainerInterface */
    private $container;

    /** @var FactoryWithTypesInterface */
    private $factory;

    /**
     * @param ContainerInterface|null $container
     * @param FactoryWithTypesInterface|null $factory
     */
    public function __construct(ContainerInterface $container, FactoryWithTypesInterface $factory)
    {
        parent::__construct();

        $this->container = $container;
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument($this->getArgumentName());

        return $name ? $this->outputItem($name, $output) : $this->outputAllItems($output);
    }

    /**
     * @param string $name
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function outputItem($name, OutputInterface $output)
    {
        if (!$this->factory->isTypeExists($name)) {
            $output->writeln(sprintf('<error>Type "%s" is not found</error>', $name));

            return 1;
        }

        $types = $this->factory->getTypes();
        try {
            $service = $this->container->get($types[$name]);
        } catch (\TypeError|\ErrorException $e) {
            $this->printErrorServiceLoadException($output, $e, $types[$name]);
            return 1;
        }

        $docCommentParser = new DocCommentParser();

        $table = new Table($output);
        $table->addRows(
            [
                ['Name', $name],
                ['Service Name', $types[$name]],
                ['Class', get_class($service)],
                ['Full Description', $docCommentParser->getFullComment(get_class($service))],
            ]
        );
        $table->render();

        return 0;
    }

    /**
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function outputAllItems(OutputInterface $output)
    {
        $types = $this->factory->getTypes();

        $table = new Table($output);
        $table->setHeaders(['Name', 'Short Description']);

        $docCommentParser = new DocCommentParser();

        foreach ($types as $key => $type) {
            try {
                $service = $this->container->get($type);
                $description = $docCommentParser->getShortComment(get_class($service));
                $table->addRow([$key, $description]);
            } catch (\TypeError|\ErrorException $e) {
                $this->printErrorServiceLoadException($output, $e, $type);
            }
        }
        $table->render();

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param \TypeError|\ErrorException $e
     * @param string $type
     */
    private function printErrorServiceLoadException(OutputInterface $output, $e, $type)
    {
        $output->writeln(sprintf('<error>Can not load Service "%s": %s</error>', $type, $e->getMessage()));
    }

    /**
     * Get name of input argument
     *
     * @return string
     */
    abstract protected function getArgumentName(): string;
}
