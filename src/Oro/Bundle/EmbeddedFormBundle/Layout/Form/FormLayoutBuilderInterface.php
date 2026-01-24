<?php

namespace Oro\Bundle\EmbeddedFormBundle\Layout\Form;

use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;

/**
 * Defines the contract for building layout blocks from form structures.
 *
 * Implementations of this interface are responsible for traversing form hierarchies
 * and creating corresponding layout blocks. This allows forms to be rendered using
 * the layout system, providing flexibility in form presentation and field organization.
 */
interface FormLayoutBuilderInterface
{
    /**
     * Builds the layout for the given form.
     */
    public function build(FormAccessorInterface $formAccessor, BlockBuilderInterface $builder, Options $options);
}
