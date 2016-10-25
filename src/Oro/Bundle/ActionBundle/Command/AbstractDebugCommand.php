<?php

namespace Oro\Bundle\ActionBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\ActionBundle\Helper\DocCommentParser;

use Oro\Component\ConfigExpression\FactoryWithTypesInterface;

abstract class AbstractDebugCommand extends ContainerAwareCommand
{
    /** @var mixed */
    protected $factory;

    /** @var DocCommentParser */
    protected $docCommentParser;

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->getContainer()->has($this->getFactoryServiceId()) && parent::isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument($this->getArgumentName());

        if ($name) {
            return $this->outputItem($name, $output);
        } else {
            return $this->outputAllItems($output);
        }
    }

    /**
     * @param string $name
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function outputItem($name, OutputInterface $output)
    {
        $types = $this->getFactory()->getTypes();
        if (!isset($types[$name])) {
            $output->writeln(sprintf('<error>Type "%s" is not found</error>', $name));

            return 1;
        }

        try {
            $service = $this->getContainer()->get($types[$name]);
        } catch (\TypeError $e) {
            $output->writeln(sprintf('<error>Can not load Service "%s": %s</error>', $types[$name], $e->getMessage()));

            return 1;
        }

        $table = new Table($output);

        $table->addRows(
            [
                ['Name', $name],
                ['Service Name', $types[$name]],
                ['Class', get_class($service)],
                ['Full Description', $this->getDocCommentParser()->getFullComment(get_class($service))],
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
        $container = $this->getContainer();

        $types = $this->getFactory()->getTypes();

        $table = new Table($output);
        $table->setHeaders(['Name', 'Short Description']);
        foreach ($types as $key => $type) {
            try {
                $service = $container->get($type);
                $description = $this->getDocCommentParser()->getShortComment(get_class($service));
                $table->addRow([$key, $description]);
            } catch (\TypeError $e) {
                $output->writeln(sprintf('<error>Can not load Service "%s": %s</error>', $type, $e->getMessage()));
            }
        }
        $table->render();

        return 0;
    }

    /**
     * @return FactoryWithTypesInterface
     */
    protected function getFactory()
    {
        if (null === $this->factory) {
            $this->factory =  $this->getContainer()->get($this->getFactoryServiceId());
        }

        return $this->factory;
    }

    /**
     * @return DocCommentParser
     */
    protected function getDocCommentParser()
    {
        if (null === $this->docCommentParser) {
            $this->docCommentParser = new DocCommentParser();
        }

        return $this->docCommentParser;
    }

    /**
     * @return string
     */
    abstract protected function getFactoryServiceId();

    /**
     * Get name of input argument
     *
     * @return string
     */
    abstract protected function getArgumentName();
}
