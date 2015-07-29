<?php

namespace Oro\Bundle\LayoutBundle\Layout\Form;

use Oro\Component\Layout\BlockBuilderInterface;

interface FormLayoutBuilderInterface
{
    /**
     * Builds the layout for the given form.
     *
     * @param FormAccessorInterface $formAccessor
     * @param BlockBuilderInterface $builder
     * @param array                 $options
     */
    public function build(FormAccessorInterface $formAccessor, BlockBuilderInterface $builder, array $options);
}
