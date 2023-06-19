<?php

namespace Oro\Bundle\EmbeddedFormBundle\Form\Type;

/**
 * Interface for embedded form type
 */
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
