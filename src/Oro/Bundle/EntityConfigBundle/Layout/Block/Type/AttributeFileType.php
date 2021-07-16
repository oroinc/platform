<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\ConfigurableType;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;

/**
 * Block type for showing file attributes.
 */
class AttributeFileType extends ConfigurableType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('visible', function (Options $options, $previousValue) {
            $expression = 'data["file_applications"].isValidForField(className, fieldName)';

            return $previousValue ? $previousValue . ' && ' . $expression : '=' . $expression;
        });
    }
}
