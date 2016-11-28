<?php

namespace Oro\Bundle\EmbeddedFormBundle\Layout\Form;

use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;

interface FormLayoutBuilderInterface
{
    /**
     * Builds the layout for the given form.
     *
     * @param FormAccessorInterface $formAccessor
     * @param BlockBuilderInterface $builder
     * @param Options               $options
     */
    public function build(FormAccessorInterface $formAccessor, BlockBuilderInterface $builder, Options $options);
}
