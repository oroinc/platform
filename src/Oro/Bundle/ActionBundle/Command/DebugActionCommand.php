<?php

namespace Oro\Bundle\ActionBundle\Command;

use Oro\Component\Action\Action\ActionFactory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugActionCommand extends ContainerAwareCommand
{
    /**
     * @var ActionFactory
     */
    protected $actionFactory;

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        if (!$this->getContainer()->has('oro_action.action_factory')) {
            return false;
        }

        $actionsFactory = $this->getContainer()->get('oro_action.action_factory');
        if (!$actionsFactory instanceof ActionFactory) {
            return false;
        }

        return parent::isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('oro:debug:action')
            ->setDescription('Displays current "actions" for an application')
            ->addArgument('action-name', InputArgument::OPTIONAL, 'An "action" name')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> displays the configured 'actions':

  <info>php %command.full_name%</info>

EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $actionName = $input->getArgument('action-name');
        $this->actionFactory = $this->getContainer()->get('oro_action.action_factory');

        if ($actionName) {
            return $this->outputDetailedInfo($actionName, $output);
        } else {
            return $this->outputTableInfo($output);
        }
    }

    /**
     * @param string $actionName
     * @param OutputInterface $output
     *
     * @return int
     */
    private function outputDetailedInfo($actionName, OutputInterface $output)
    {
        $types = $this->actionFactory->getTypes();
        if (!isset($types[$actionName])) {
            $output->writeln(sprintf('Action "%s" not found', $actionName));

            return 1;
        }

        try {
            $action = $this->getContainer()->get($types[$actionName]);
        } catch (\TypeError $e) {
            $output->writeln(
                sprintf(
                    'Unable to load action "%s", due error "%s"',
                    $actionName,
                    PHP_EOL . $e->getMessage()
                )
            );

            return 1;
        }

        $table = new Table($output);

        $reflection = new \ReflectionClass(get_class($action));

        $table->addRows(
            [
                ['Name', $actionName],
                ['Service Name', $types[$actionName]],
                ['Class', get_class($action)],
                ['Full Description', $this->getFullDescription(get_class($action))],
                ['Arguments', $reflection->getConstructor()],
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
    private function outputTableInfo(OutputInterface $output)
    {
        $types = $this->actionFactory->getTypes();
        $table = new Table($output);
        $table->setHeaders(['Name', 'Short Description']);
        foreach ($types as $key => $type) {
            $description = "No Description";
            try {
                $action = $this->getContainer()->get($type);
                $description = $this->getShortDescription(get_class($action));
            } catch (\TypeError $e) {
            }
            $table->addRow([$key, $description]);
        }
        $table->render();

        return 0;
    }

    /**
     * Returns only first line from full description
     *
     * @param string $className
     *
     * @return string
     */
    private function getShortDescription($className)
    {
        return $this->getFullDescription($className);
    }

    /**
     * @param string $className
     *
     * @return string
     */
    private function getFullDescription($className)
    {
        $description = sprintf('No Description Found For "%s"', $className);
        try {
            $reflection = new \ReflectionClass($className);
            $doc = $reflection->getDocComment();
            $description = strlen($doc) ? $doc : $description;
        } catch (\ReflectionException $e) {
        }

        return $description;
    }
}
