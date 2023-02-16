<?php

namespace Oro\Bundle\EntityConfigBundle\Async\Topic;

use Oro\Bundle\ImportExportBundle\Async\Topic\ImportTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for importing attributes from a file.
 */
class AttributeImportTopic extends ImportTopic
{
    public const NAME = 'oro_entity_config.importexport.attribute.import';

    public static function getName(): string
    {
        return self::NAME;
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        parent::configureMessageBody($resolver);

        $resolver
            ->setRequired('subJobs')
            ->setAllowedTypes('subJobs', 'array[]');
    }
}
