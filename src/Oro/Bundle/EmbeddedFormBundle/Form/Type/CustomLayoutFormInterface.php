<?php
namespace Oro\Bundle\EmbeddedFormBundle\Form\Type;

/**
 * @deprecated since 1.7. Please use LayoutUpdateInterface instead.
 */
interface CustomLayoutFormInterface
{
    /**
     * @return string - e.g. bundle:controller:template
     */
    public function getFormLayout();
}
