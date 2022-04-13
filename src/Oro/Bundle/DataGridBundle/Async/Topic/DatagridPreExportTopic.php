<?php

namespace Oro\Bundle\DataGridBundle\Async\Topic;

use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Initializes export process.
 */
class DatagridPreExportTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.datagrid.pre_export';
    }

    public static function getDescription(): string
    {
        return 'Initializes datagrid data export.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'format',
                'notificationTemplate',
                'parameters',
            ])
            ->setRequired([
                'format',
            ])
            ->setDefaults([
                'parameters' => function (OptionsResolver $parametersResolver) {
                    $parametersResolver
                        ->setDefined([
                            'gridName',
                            'parameters',
                            FormatterProvider::FORMAT_TYPE,
                        ])
                        ->setRequired([
                            'gridName',
                        ])
                        ->setDefaults([
                            'gridParameters' => [],
                            FormatterProvider::FORMAT_TYPE => 'excel',
                        ])
                      ->setAllowedTypes('gridName', 'string')
                      ->setAllowedTypes('gridParameters', 'array')
                      ->setAllowedTypes(FormatterProvider::FORMAT_TYPE, 'string');
                },
                'notificationTemplate' => null,
            ])
            ->addAllowedTypes('format', 'string')
            ->addAllowedTypes('parameters', 'array')
            ->addAllowedTypes('notificationTemplate', ['string', 'null']);
    }
}
