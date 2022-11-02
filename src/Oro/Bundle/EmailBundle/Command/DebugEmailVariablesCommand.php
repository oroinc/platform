<?php
declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Command;

use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariablesProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Displays email template variables
 */
class DebugEmailVariablesCommand extends Command
{
    protected static $defaultName = 'oro:debug:email:variables';

    protected static $defaultDescription = 'Displays email template variables';

    private DoctrineHelper $doctrineHelper;

    private EmailRenderer $emailRenderer;

    private VariablesProvider $emailVariablesProvider;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EmailRenderer $emailRenderer,
        VariablesProvider $emailVariablesProvider
    ) {
        parent::__construct();

        $this->doctrineHelper = $doctrineHelper;
        $this->emailRenderer = $emailRenderer;
        $this->emailVariablesProvider = $emailVariablesProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption(
                'entity-class',
                null,
                InputOption::VALUE_OPTIONAL,
                'Entity class.'
            )
            ->addOption(
                'entity-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Entity ID.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('System Variables');
        $this->processSystemVariables($output);

        if ($input->getOption('entity-class')) {
            $output->writeln('');
            $output->writeln('Entity Variables');
            $this->processEntityVariables($output, $input->getOption('entity-class'), $input->getOption('entity-id'));
        }

        return 0;
    }

    /**
     * @param OutputInterface $output
     */
    private function processSystemVariables(OutputInterface $output)
    {
        $table = new Table($output);
        $headers = [
            'Name',
            'Title',
            'Type',
            'Value',
        ];

        $table->setHeaders($headers)->setRows([]);
        foreach ($this->emailVariablesProvider->getSystemVariableDefinitions() as $variable => $definition) {
            $data = [
                'system.' . $variable,
                $definition['label'] ?? 'N/A',
                $definition['type'] ?? 'mixed',
            ];
            $data[] = $this->emailRenderer->renderTemplate(sprintf('{{ system.%s }}', $variable));

            $table->addRow($data);
        }
        $table->render();
    }

    /**
     * @param OutputInterface $output
     * @param string $entityClass
     * @param null|mixed $entityId
     */
    private function processEntityVariables(OutputInterface $output, $entityClass, $entityId = null)
    {
        $entityClass = $this->doctrineHelper->getEntityClass($entityClass);
        $entity = $entityId ? $this->getEntity($entityClass, $entityId) : null;

        $table = new Table($output);
        $headers = [
            'Name',
            'Title',
            'Type',
        ];

        if ($entity) {
            $headers[] = 'Value';
        }

        $table->setHeaders($headers)->setRows([]);
        $variables = $this->emailVariablesProvider->getEntityVariableDefinitions();
        $variables = $variables[$entityClass] ?? [];

        foreach ($variables as $variable => $definition) {
            $data = [
                'entity.' . $variable,
                $definition['label'],
                $definition['type'],
            ];

            if ($entity) {
                if ($definition['type'] !== 'image') {
                    $data[] = $this->emailRenderer->renderTemplate(
                        sprintf('{{ entity.%s }}', $variable),
                        ['entity' => $entity]
                    );
                } else {
                    $data[] = sprintf('<info>Type "%s" skipped for CLI</info>', $definition['type']);
                }
            }
            $table->addRow($data);
        }

        $table->render();
    }

    /**
     * @param string $entityClass
     * @param null|mixed $entityId
     *
     * @return object
     */
    private function getEntity(string $entityClass, $entityId = null)
    {
        $entity = $this->doctrineHelper->createEntityInstance($entityClass);

        if ($entityId) {
            $entity = $this->doctrineHelper->getEntity($entityClass, $entityId);

            if (!$entity) {
                throw new \RuntimeException(sprintf('Entity "%s" with id "%s" not found', $entityClass, $entityId));
            }
        }

        return $entity;
    }
}
