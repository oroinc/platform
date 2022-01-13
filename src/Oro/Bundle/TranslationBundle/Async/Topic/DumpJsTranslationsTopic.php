<?php

namespace Oro\Bundle\TranslationBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Dump JS translations.
 */
class DumpJsTranslationsTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.translation.dump_js_translations';
    }

    public static function getDescription(): string
    {
        return 'Dump JS translations';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
    }
}
