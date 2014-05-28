<?php

namespace Oro\Bundle\NoteBundle\Tools;

use CG\Generator\PhpClass;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityGeneratorExtension;

class NoteExtendGenerator implements ExtendEntityGeneratorExtension
{
    /**
     * {@inheritdoc}
     */
    public function supports($actionType, array $config)
    {
        // TODO: check if there's at least one entity using notes
    }

    /**
     * {@inheritdoc}
     */
    public function preProcessEntityConfiguration(array &$config)
    {
        // can change config here
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array &$config, PhpClass $class)
    {
        // TODO: generate unidirectional relations to entities that use notes
    }
}
