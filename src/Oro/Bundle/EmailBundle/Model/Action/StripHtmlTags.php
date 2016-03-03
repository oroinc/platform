<?php

namespace Oro\Bundle\EmailBundle\Model\Action;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;

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

    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /**
     * @param ContextAccessor $contextAccessor
     * @param HtmlTagHelper   $htmlTagHelper
     */
    public function __construct(ContextAccessor $contextAccessor, HtmlTagHelper $htmlTagHelper)
    {
        parent::__construct($contextAccessor);
        $this->htmlTagHelper = $htmlTagHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $result = $this->htmlTagHelper->purify($this->contextAccessor->getValue($context, $this->html));
        $result = $this->htmlTagHelper->stripTags($result);

        $this->contextAccessor->setValue($context, $this->attribute, $result);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
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
