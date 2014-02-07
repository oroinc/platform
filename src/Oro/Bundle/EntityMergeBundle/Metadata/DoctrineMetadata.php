<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

class DoctrineMetadata extends Metadata implements MetadataInterface
{
    /** @todo Move this const to another class? Add special method for getting this option? */
    const OPTION_NAME = 'doctrine_mapping';
}
