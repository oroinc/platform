<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageFactory;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;

/**
 * Initializes Behat contexts with page and element factories.
 *
 * This initializer injects the {@see OroPageFactory} and {@see OroElementFactory} into any Behat context
 * that implements {@see OroPageObjectAware}, enabling contexts to interact with page objects and
 * UI elements during test execution.
 */
class OroPageObjectInitializer implements ContextInitializer
{
    /**
     * @var OroElementFactory
     */
    protected $elementFactory;

    /**
     * @var OroPageFactory
     */
    protected $pageFactory;

    public function __construct(OroElementFactory $elementFactory, OroPageFactory $pageFactory)
    {
        $this->elementFactory = $elementFactory;
        $this->pageFactory = $pageFactory;
    }

    #[\Override]
    public function initializeContext(Context $context)
    {
        if ($context instanceof OroPageObjectAware) {
            $context->setElementFactory($this->elementFactory);
            $context->setPageFactory($this->pageFactory);
        }
    }
}
