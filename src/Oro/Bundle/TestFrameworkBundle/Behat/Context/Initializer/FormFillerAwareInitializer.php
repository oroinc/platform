<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Oro\Bundle\TestFrameworkBundle\Behat\FormFiller\FormFiller;
use Oro\Bundle\TestFrameworkBundle\Behat\FormFiller\FormFillerAware;

class FormFillerAwareInitializer implements ContextInitializer
{
    /**
     * @var FormFiller
     */
    protected $formFiller;

    /**
     * @param FormFiller $formFiller
     */
    public function __construct(FormFiller $formFiller)
    {
        $this->formFiller = $formFiller;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof FormFillerAware) {
            $context->setFormFiller($this->formFiller);
        }
    }
}
