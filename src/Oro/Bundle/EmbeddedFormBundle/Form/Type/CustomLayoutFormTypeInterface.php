<?php
namespace Oro\Bundle\EmbeddedFormBundle\Form\Type;

/**
 * @deprecated since 1.3 because of typo. Please use CustomLayoutFormInterface instead.
 */
interface CustomLayoutFormTypeInterface
{
    /**
     * @return string - e.g. bundle:controller:template
     */
    public function geFormLayout();
}
