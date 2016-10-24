<?php

namespace Oro\Bundle\ActionBundle\Command;

use Oro\Component\ConfigExpression\ExpressionFactory;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class DebugConditionCommand extends ContainerAwareCommand
{
    /**
     * @var ExpressionFactory
     */
    protected $expressionFactory;

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        if (!$this->getContainer()->has('oro_action.expression.factory')) {
            return false;
        }

        $actionsFactory = $this->getContainer()->get('oro_action.expression.factory');
        if (!$actionsFactory instanceof ExpressionFactory) {
            return false;
        }

        return parent::isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('oro:debug:condition')
            ->setDescription('Displays enable condition')
            ->addArgument('condition-name', InputArgument::OPTIONAL, 'A condition name')
            ->setHelp(<<<EOF
The <info>%command.name%</info> displays list of all conditions with full description:

  <info>php %command.full_name%</info>

EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conditionName = $input->getArgument('condition-name');
        $this->expressionFactory = $this->getContainer()->get('oro_action.expression.factory');

        if ($conditionName) {
            return $this->outputDetailedInfo($conditionName, $output);
        } else {
            return $this->outputShortInfo($output);
        }
    }

    /**
     * @param string $conditionName
     * @param OutputInterface $output
     *
     * @return int
     */
    private function outputDetailedInfo($conditionName, OutputInterface $output)
    {
        $extensions = $this->expressionFactory->getExtensions();
        if (!array_key_exists($conditionName, $extensions)) {
            $output->writeln(sprintf('Condition "%s" not found', $conditionName));

            return 1;
        }

        try {
            $extension = $this->getContainer()->get($extensions[$conditionName]);
        } catch (ServiceNotFoundException $e) {
            $output->writeln(
                sprintf('Unable to find condition "%s", due error "%s"',
                    $conditionName,
                    PHP_EOL . $e->getMessage()
                )
            );

            return 1;
        }

        $table = new Table($output);

        $reflection = new \ReflectionClass(get_class($extension));

        $table->addRows(
            [
                ['Name', $conditionName],
                ['Service Name', $extensions[$conditionName]],
                ['Class', get_class($extension)],
                ['Full Description', $this->getFullDescription(get_class($extension))],
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
    private function outputShortInfo(OutputInterface $output)
    {
        $extensions = $this->expressionFactory->getExtensions();
        $table = new Table($output);
        $table->setHeaders(['Name', 'Short Description']);
        foreach ($extensions as $key => $condition) {
            try {
                $condition = $this->getContainer()->get($condition);
                $description = $this->getShortDescription(get_class($condition));
            } catch (ServiceNotFoundException $e) {
                $description = "No Description";
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
        $fullDescription = $this->getFullDescription($className);
        list($shortDescription) = explode("\n", $fullDescription);
        return $shortDescription;
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
            $description = strlen($doc) ? $this->parserDocComment($doc) : $description;
        } catch (\ReflectionException $e) {
        }
        return $description;
    }

    /**
     * @param string $docComment
     *
     * @return string
     */
    private function parserDocComment($docComment)
    {
        $docComment = substr($docComment, 3, -2);
        return preg_replace('/\s\*\s/', '', $docComment);
    }
}
