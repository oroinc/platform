<?php

namespace Oro\Bundle\EmbeddedFormBundle\Layout\Form;

use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;

interface FormLayoutBuilderInterface
{
    /**
     * Builds the layout for the given form.
     */
    public function build(FormAccessorInterface $formAccessor, BlockBuilderInterface $builder, Options $options);
}
