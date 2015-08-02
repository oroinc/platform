<?php

namespace Oro\Bundle\EmailBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;

/**
 * Strips html tags from string.
 *
 * Example:
 * @strip_html_tags:
 *     attribute: $.textOnly
 *     html: $.html
 *
 * Class StripHtmlTags
 * @package Oro\Bundle\EmailBundle\Model\Action
 */
class StripHtmlTags extends AbstractAction
{
    /** @var string */
    protected $attribute;
    /** @var string */
    protected $html;

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $result = strip_tags($this->contextAccessor->getValue($context, $this->html));

        $this->contextAccessor->setValue($context, $this->attribute, $result);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!isset($options['attribute']) && !isset($options[0])) {
            throw new InvalidParameterException('Attribute must be defined.');
        }

        if (!isset($options['html']) && !isset($options[1])) {
            throw new InvalidParameterException('Html must be defined.');
        }

        $this->attribute = isset($options['attribute']) ? $options['attribute'] : $options[0];
        $this->html      = isset($options['html'])      ? $options['html']      : $options[1];
    }
}
