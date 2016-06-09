<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactoryAware;

class ElementFactoryInitializer implements ContextInitializer
{
    /**
     * @var OroElementFactory
     */
    protected $elementFactory;

    /**
     * @param OroElementFactory $elementFactory
     */
    public function __construct(OroElementFactory $elementFactory)
    {
        $this->elementFactory = $elementFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof OroElementFactoryAware) {
            $context->setElementFactory($this->elementFactory);
        }
    }
}
