<?php

namespace Oro\Component\Action\Action;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Shows a flash message.
 * Usage:
 * @flash_message:
 *      message: 'Message %parameter_one%, %parameter_two%'
 *      type: 'info'
 *      message_parameters:
 *          parameter_one: 'test'
 *          parameter_two: $someEntity.name
 */
class FlashMessage extends AbstractAction
{
    const DEFAULT_MESSAGE_TYPE = 'info';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /** @var RequestStack */
    protected $requestStack;

    /** @var string|PropertyPathInterface */
    protected $message;

    /** @var bool|PropertyPathInterface|null */
    protected $translate;

    /** @var string|PropertyPathInterface|null */
    protected $type;

    /** @var array|PropertyPathInterface|null */
    protected $messageParameters;

    public function __construct(
        ContextAccessor $contextAccessor,
        TranslatorInterface $translator,
        HtmlTagHelper $htmlTagHelper,
        RequestStack $requestStack
    ) {
        parent::__construct($contextAccessor);
        $this->translator = $translator;
        $this->htmlTagHelper = $htmlTagHelper;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $message = $this->contextAccessor->getValue($context, $this->message);
        $messageParameters = $this->getMessageParameters($context);
        if ($this->contextAccessor->getValue($context, $this->translate) ?? true) {
            $message = $this->translator->trans($message, $messageParameters);
        } elseif ($messageParameters) {
            $message = strtr($message, $messageParameters);
        }

        $request->getSession()->getFlashBag()->add(
            $this->contextAccessor->getValue($context, $this->type) ?? self::DEFAULT_MESSAGE_TYPE,
            $this->htmlTagHelper->sanitize($message)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $message = $options['message'] ?? null;
        if (!$message) {
            throw new InvalidParameterException('Parameter "message" is required.');
        }
        if (!empty($options['type'])) {
            $this->type = $options['type'];
        }
        if (!empty($options['message_parameters'])) {
            $this->messageParameters = $options['message_parameters'];
        }
        if (isset($options['translate'])) {
            $this->translate = $options['translate'];
        }

        $this->message = $message;

        return $this;
    }

    /**
     * @param mixed $context
     *
     * @return array
     */
    protected function getMessageParameters($context)
    {
        $result = [];
        $parameters = (array)$this->contextAccessor->getValue($context, $this->messageParameters);
        foreach ($parameters as $key => $parameter) {
            $result['%' . $key . '%'] = $this->contextAccessor->getValue($context, $parameter);
        }

        return $result;
    }
}
