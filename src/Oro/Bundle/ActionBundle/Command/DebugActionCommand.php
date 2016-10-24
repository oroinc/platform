<?php

namespace Oro\Bundle\ActionBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

use Oro\Component\Action\Action\ActionFactory;

class DebugActionCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:debug:action';

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
        $this->setName(self::COMMAND_NAME)
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
                    '<error>Unable to load action "%s", due error "%s"</error>',
                    $actionName,
                    PHP_EOL . $e->getMessage()
                )
            );

            return 1;
        }

        $table = new Table($output);

        $table->addRows(
            [
                ['Name', $actionName],
                ['Service Name', $types[$actionName]],
                ['Class', get_class($action)],
                ['Full Description', $this->getFullDescription(get_class($action), $output)],
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
        $container = $this->getContainer();
        $types = $this->actionFactory->getTypes();
        $table = new Table($output);
        $table->setHeaders(['Name', 'Short Description']);
        foreach ($types as $key => $type) {
            try {
                $action = $container->get($type);
            } catch (ServiceNotFoundException $e) {
                $output->writeln(sprintf('<error>Can not load Action "%s"</error>', $type));

                throw $e;
            }
            $description = $this->getShortDescription(get_class($action), $output);
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
    private function getShortDescription($className, OutputInterface $output)
    {
        // remove lines after the latest empty string
        return trim(preg_replace('#\n\s*\n.+$#s', '', $this->getFullDescription($className, $output)));
    }

    /**
     * @param string $className
     *
     * @return string
     */
    private function getFullDescription($className, OutputInterface $output)
    {
        $description = '';
        try {
            $reflection = new \ReflectionClass($className);
            $description = $reflection->getDocComment();
            $description = $this->filterDescription($description);
        } catch (\ReflectionException $e) {
            $output->writeln(
                sprintf(
                    '<error>Can not get Doc Comment for class "%s": %s</error>',
                    $className,
                    $e->getMessage()
                )
            );
        }

        return $description;
    }

    /**
     * @param string $description
     *
     * @return string
     */
    private function filterDescription($description)
    {
        $regExps = [
            '#/?\*+.?#',
            '#^\s*@(package|SuppressWarning).+$#mi',
        ];
        foreach ($regExps as $exp) {
            $description = trim(preg_replace($exp, '', $description));
        }

        return $description;
    }
}
