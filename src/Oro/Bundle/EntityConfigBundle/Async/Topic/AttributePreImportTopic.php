<?php

namespace Oro\Bundle\EntityConfigBundle\Async\Topic;

use Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic;

/**
 * Topic for splitting import process into a set of independent jobs.
 */
class AttributePreImportTopic extends PreImportTopic
{
    public const NAME = 'oro_entity_config.importexport.attribute.pre_import';
}
