<?php

namespace Oro\Bundle\DraftBundle\Action;

use Oro\Bundle\DraftBundle\Duplicator\Duplicator;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Responsible for create draft from object.
 */
class DraftCreateAction extends AbstractAction
{
    private const OPTION_KEY_SOURCE = 'source';
    private const OPTION_KEY_TARGET = 'target';

    /**
     * @var Duplicator
     */
    private $duplicator;

    /**
     * @var array
     */
    private $options;

    /**
     * @param ContextAccessor $contextAccessor
     * @param Duplicator $duplicator
     */
    public function __construct(ContextAccessor $contextAccessor, Duplicator $duplicator)
    {
        parent::__construct($contextAccessor);
        $this->duplicator = $duplicator;
    }

    /**
     * @param \ArrayAccess $context
     */
    protected function executeAction($context)
    {
        $source = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_SOURCE]);
        if (!$source instanceof DraftableInterface) {
            throw new \LogicException('The parameter \'source\' must be instance of DraftableInterface');
        }

        if (!$context instanceof \ArrayAccess) {
            throw new \LogicException('The parameter \'context\' must be instance of ArrayAccess');
        }

        $target = $this->duplicator->duplicate($source, $context);
        $this->contextAccessor->setValue($context, $this->options[self::OPTION_KEY_TARGET], $target);
    }

    /**
     * @param array $options
     *
     * @return $this|ActionInterface
     */
    public function initialize(array $options)
    {
        $this->getOptionResolver()->resolve($options);
        $this->options = $options;

        $this->getOption($options, self::OPTION_KEY_SOURCE);

        return $this;
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionResolver(): OptionsResolver
    {
        $optionResolver = new OptionsResolver();
        $optionResolver->setRequired(self::OPTION_KEY_TARGET);
        $optionResolver->setRequired(self::OPTION_KEY_SOURCE);
        $optionResolver->setAllowedTypes(self::OPTION_KEY_SOURCE, ['object', PropertyPathInterface::class]);
        $optionResolver->setAllowedTypes(self::OPTION_KEY_TARGET, ['object', PropertyPathInterface::class]);

        return $optionResolver;
    }
}
