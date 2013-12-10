<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class FormatName extends AbstractAction
{
    /**
     * @var NameFormatter
     */
    protected $formatter;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param ContextAccessor $contextAccessor
     * @param NameFormatter $formatter
     */
    public function __construct(ContextAccessor $contextAccessor, NameFormatter $formatter)
    {
        parent::__construct($contextAccessor);

        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options['attribute'])) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }
        if (empty($options['object'])) {
            throw new InvalidParameterException('Object parameter is required');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $this->contextAccessor->setValue(
            $context,
            $this->options['attribute'],
            $this->formatter->format(
                $this->contextAccessor->getValue($context, $this->options['object'])
            )
        );
    }
}
