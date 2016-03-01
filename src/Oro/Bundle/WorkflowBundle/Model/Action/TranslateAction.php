<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class TranslateAction extends AbstractAction
{
    const OPTION_KEY_ATTRIBUTE  = 'attribute';
    const OPTION_KEY_ID         = 'id';
    const OPTION_KEY_PARAMETERS = 'params';
    const OPTION_KEY_DOMAIN     = 'domain';
    const OPTION_KEY_LOCALE     = 'locale';

    /** @var TranslatorInterface */
    protected $translator;
    /** @var array */
    protected $options;

    /**
     * @param ContextAccessor     $contextAccessor
     * @param TranslatorInterface $translator
     */
    public function __construct(ContextAccessor $contextAccessor, TranslatorInterface $translator)
    {
        parent::__construct($contextAccessor);
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $id = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_ID]);

        if (isset($this->options[self::OPTION_KEY_PARAMETERS])) {
            $parameters = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_PARAMETERS]);
        } else {
            $parameters = [];
        }

        $domain = null;
        if (isset($this->options[self::OPTION_KEY_DOMAIN])) {
            $domain = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_DOMAIN]);
        }

        $locale = null;
        if (isset($this->options[self::OPTION_KEY_LOCALE])) {
            $locale = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_LOCALE]);
        }

        $result = $this->translator->trans($id, $parameters, $domain, $locale);

        $this->contextAccessor->setValue($context, $this->options[self::OPTION_KEY_ATTRIBUTE], $result);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::OPTION_KEY_ID])) {
            throw new InvalidParameterException('ID parameter is required');
        }

        if (empty($options[self::OPTION_KEY_ATTRIBUTE])) {
            throw new InvalidParameterException('Attribute parameter is required');
        } elseif (!$options[self::OPTION_KEY_ATTRIBUTE] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition');
        }

        $this->options = $options;
    }
}
