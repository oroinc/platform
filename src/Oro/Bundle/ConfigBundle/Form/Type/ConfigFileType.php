<?php

namespace Oro\Bundle\ConfigBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;

class ConfigFileType extends FileType
{
    const NAME = 'oro_config_file';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
