<?php

namespace Oro\Bundle\EntityConfigBundle\Metadata\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Exception\AnnotationException;

/**
 * The annotation that is used to provide configuration for fields of configurable entity.
 * @Annotation
 * @Target("PROPERTY")
 */
final class ConfigField
{
    private const MODES = [ConfigModel::MODE_DEFAULT, ConfigModel::MODE_HIDDEN, ConfigModel::MODE_READONLY];

    public string $mode = ConfigModel::MODE_DEFAULT;
    public array $defaultValues = [];

    public function __construct(array $data)
    {
        if (isset($data['mode'])) {
            $this->mode = $data['mode'];
        } elseif (isset($data['value'])) {
            $this->mode = $data['value'];
        }
        if (isset($data['defaultValues'])) {
            $this->defaultValues = $data['defaultValues'];
        }

        if (!\in_array($this->mode, self::MODES, true)) {
            throw new AnnotationException(sprintf(
                'Annotation "ConfigField" give invalid parameter "mode" : "%s"',
                $this->mode
            ));
        }
    }
}
