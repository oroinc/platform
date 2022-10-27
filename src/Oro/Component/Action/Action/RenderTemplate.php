<?php

namespace Oro\Component\Action\Action;

use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Twig\Environment;

/**
 * Action for rendering templates
 */
class RenderTemplate extends AbstractAction
{
    private const ATTRIBUTE = 'attribute';
    private const TEMPLATE = 'template';
    private const PARAMS = 'params';

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var array
     */
    private $options;

    public function __construct(ContextAccessor $contextAccessor, Environment $twig)
    {
        parent::__construct($contextAccessor);

        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $this->options = $this->getOptionResolver()->resolve($options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $params = [];
        foreach ($this->options['params'] as $key => $param) {
            $params[$key] = $this->contextAccessor->getValue($context, $param);
        }

        $this->contextAccessor->setValue(
            $context,
            $this->options['attribute'],
            $this->twig->render($this->options['template'], $params)
        );
    }

    private function getOptionResolver(): OptionsResolver
    {
        $optionResolver = new OptionsResolver();
        $optionResolver->setRequired([self::ATTRIBUTE, self::TEMPLATE, self::PARAMS]);
        $optionResolver->setDefault(self::PARAMS, []);
        $optionResolver->setAllowedTypes(self::ATTRIBUTE, ['object', PropertyPathInterface::class]);
        $optionResolver->setAllowedTypes(self::TEMPLATE, ['string', PropertyPathInterface::class]);

        return $optionResolver;
    }
}
