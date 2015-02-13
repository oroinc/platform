<?php

namespace Oro\Bundle\LayoutBundle\Layout\Form;

use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\BlockBuilderInterface;

interface FormLayoutBuilderInterface
{
    /**
     * Builds the layout for the given form.
     *
     * @param FormInterface         $form
     * @param BlockBuilderInterface $builder
     * @param array                 $options
     */
    public function build(FormInterface $form, BlockBuilderInterface $builder, array $options);
}
