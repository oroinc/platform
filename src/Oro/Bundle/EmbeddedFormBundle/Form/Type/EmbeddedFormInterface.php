<?php
namespace Oro\Bundle\EmbeddedFormBundle\Form\Type;

interface EmbeddedFormInterface
{
    /**
     * @return string
     */
    public function getDefaultCss();

    /**
     * @return string
     */
    public function getDefaultSuccessMessage();
}
