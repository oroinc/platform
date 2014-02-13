<?php
namespace Oro\Bundle\EmbeddedFormBundle\Form\Type;

interface CustomLayoutFormTypeInterface
{
    /**
     * @return string - e.g. bundle:controller:template
     */
    public function geFormLayout();
}
