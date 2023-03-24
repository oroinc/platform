<?php

namespace Oro\Bundle\ImportExportBundle\Async\Topic;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for importing a file.
 */
abstract class AbstractImportTopic extends AbstractTopic
{
    protected int $batchSize;

    public function __construct(int $batchSize)
    {
        $this->batchSize = $batchSize;
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $batchSize = $this->batchSize;

        $resolver
            ->setDefined([
                'userId',
                'jobName',
                'process',
                'processorAlias',
                'fileName',
                'originFileName',
                'options',
            ])
            ->setRequired([
                'userId',
                'jobName',
                'process',
                'processorAlias',
                'fileName',
                'originFileName',
            ])
            ->setDefaults([
                'options' => [],
            ])
            ->setNormalizer('options', static function (Options $options, $value) use ($batchSize) {
                if (!array_key_exists(Context::OPTION_BATCH_SIZE, $value)) {
                    $value[Context::OPTION_BATCH_SIZE] = $batchSize;
                }

                if (!is_int($value[Context::OPTION_BATCH_SIZE])) {
                    throw new InvalidOptionsException(
                        sprintf(
                            'The option "options[%s]" is expected to be of type "int".',
                            Context::OPTION_BATCH_SIZE
                        )
                    );
                }

                return $value;
            })
            ->addAllowedTypes('userId', 'int')
            ->addAllowedTypes('jobName', 'string')
            ->addAllowedTypes('process', 'string')
            ->addAllowedTypes('processorAlias', 'string')
            ->addAllowedTypes('fileName', 'string')
            ->addAllowedTypes('originFileName', 'string')
            ->addAllowedTypes('options', 'array');
    }
}
