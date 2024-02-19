<?php

namespace Oro\Bundle\DataGridBundle\Async\Topic;

use Oro\Bundle\DataGridBundle\Provider\ChainConfigurationProvider;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines MQ topic that should export a batch of rows during the datagrid data export.
 */
class DatagridExportTopic extends AbstractTopic
{
    /** @var ChainConfigurationProvider $configurationProvider  */
    private ConfigurationProviderInterface $configurationProvider;

    public function setConfigurationProvider(ConfigurationProviderInterface $configurationProvider): void
    {
        $this->configurationProvider = $configurationProvider;
    }

    public static function getName(): string
    {
        return 'oro.datagrid.export';
    }

    public static function getDescription(): string
    {
        return 'Exports a batch of rows during the datagrid data export.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined(
                [
                    'jobId',
                    'outputFormat',
                    'writerBatchSize',
                    'contextParameters',
                ]
            )
            ->setRequired(
                [
                    'jobId',
                    'outputFormat',
                    'contextParameters',
                ]
            )
            ->setDefaults(
                [
                    'writerBatchSize' => 100,
                    'contextParameters' => \Closure::fromCallable([$this, 'configureContextParameters']),
                ]
            )
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('outputFormat', 'string')
            ->addAllowedTypes('writerBatchSize', 'int')
            ->addAllowedTypes('contextParameters', 'array')
            ->setInfo('writerBatchSize', 'Number of rows to collect before sending them to the export writer.');
    }

    private function configureContextParameters(OptionsResolver $parametersResolver): void
    {
        $parametersResolver
            ->setDefined(
                [
                    'gridName',
                    'gridParameters',
                    FormatterProvider::FORMAT_TYPE,
                    'materializedViewName',
                    'rowsOffset',
                    'rowsLimit',
                ]
            )
            ->setRequired(
                [
                    'gridName',
                    'materializedViewName',
                    'rowsOffset',
                    'rowsLimit',
                ]
            )
            ->setDefaults(
                [
                    'gridParameters' => [],
                    FormatterProvider::FORMAT_TYPE => 'excel',
                ]
            )
            ->setAllowedTypes('gridName', 'string')
            ->addAllowedValues('gridName', function (string $gridName) {
                try {
                    $this->configurationProvider->getConfiguration($gridName);
                } catch (\Throwable $e) {
                    throw new InvalidOptionsException(sprintf('Grid %s configuration is not valid', $gridName));
                }

                return true;
            })
            ->setAllowedTypes('gridParameters', 'array')
            ->setAllowedTypes(FormatterProvider::FORMAT_TYPE, 'string')
            ->setAllowedTypes('materializedViewName', 'string')
            ->setAllowedTypes('rowsOffset', 'int')
            ->setAllowedTypes('rowsLimit', 'int');
    }
}
