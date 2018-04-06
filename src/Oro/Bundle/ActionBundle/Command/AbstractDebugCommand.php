<?php

namespace Oro\Bundle\ActionBundle\Command;

use Oro\Bundle\ActionBundle\Helper\DocCommentParser;
use Oro\Component\ConfigExpression\FactoryWithTypesInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $types = $this->getFactory()->getTypes();
        if (!isset($types[$name])) {
            $output->writeln(sprintf('<error>Type "%s" is not found</error>', $name));

            return 1;
        }

        try {
            $service = $this->getContainer()->get($types[$name]);
        } catch (\TypeError $e) {
            $this->printErrorServiceLoadException($output, $e, $types[$name]);
            return 1;
        } catch (\ErrorException $e) { //php 5.6 compatibility
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
        $types = $this->getFactory()->getTypes();

        $table = new Table($output);
        $table->setHeaders(['Name', 'Short Description']);

        $docCommentParser = new DocCommentParser();

        foreach ($types as $key => $type) {
            try {
                $service = $this->getContainer()->get($type);
                $description = $docCommentParser->getShortComment(get_class($service));
                $table->addRow([$key, $description]);
            } catch (\TypeError $e) {
                $this->printErrorServiceLoadException($output, $e, $type);
            } catch (\ErrorException $e) { //php 5.6 compatibility
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
     * @return FactoryWithTypesInterface
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
     * Get name of input argument
     *
     * @return string
     */
    abstract protected function getArgumentName();
}
