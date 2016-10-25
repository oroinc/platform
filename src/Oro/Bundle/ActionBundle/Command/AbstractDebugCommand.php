<?php

namespace Oro\Bundle\ActionBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\ActionBundle\Helper\DocCommentParser;

abstract class AbstractDebugCommand extends ContainerAwareCommand
{
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
        $types = $this->getTypes();
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
        $types = $this->getTypes();

        $table = new Table($output);
        $table->setHeaders(['Name', 'Short Description']);

        $docCommentParser = new DocCommentParser();

        foreach ($types as $key => $type) {
            try {
                $service = $this->getContainer()->get($type);
                $description = $docCommentParser->getShortComment(get_class($service));
                $table->addRow([$key, $description]);
            } catch (\TypeError $e) {
                $output->writeln(sprintf('<error>Can not load Service "%s": %s</error>', $type, $e->getMessage()));
            }
        }
        $table->render();

        return 0;
    }

    /**
     * @return mixed
     */
    protected function getFactory()
    {
        return $this->getContainer()->get($this->getFactoryServiceId());
    }

    /**
     * @return string
     */
    abstract protected function getFactoryServiceId();

    /**
     * @return array
     */
    abstract protected function getTypes();

    /**
     * Get name of input argument
     *
     * @return string
     */
    abstract protected function getArgumentName();
}
