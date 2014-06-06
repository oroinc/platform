<?php
namespace Oro\Bundle\EmbeddedFormBundle\Form\Type;

interface CustomLayoutFormInterface
{
    /**
     * @return string - e.g. bundle:controller:template
     */
    public function getFormLayout();
}
