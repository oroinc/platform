<?php

namespace Oro\Bundle\DraftBundle\Action;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Action\ActionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Base class of draft actions
 */
abstract class AbstractDraftAction extends AbstractAction
{
    public const OPTION_KEY_SOURCE = 'source';
    public const OPTION_KEY_TARGET = 'target';

    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     *
     * @return $this|ActionInterface
     */
    public function initialize(array $options): ActionInterface
    {
        $this->options = $this->getOptionResolver()->resolve($options);

        return $this;
    }

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
