<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\FormFiller;

interface FormFillerAware
{
    /**
     * @param FormFiller $formFiller
     *
     * @return null
     */
    public function setFormFiller(FormFiller $formFiller);
}
