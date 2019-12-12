<?php

namespace Oro\Bundle\DraftBundle\Action;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Assign not draftable entity fields to confirmation message
 */
class AssignDraftableFields extends AbstractAction
{
    private const OBJECT = 'object';
    private const ATTRIBUTE = 'attribute';

    /**
     * @var DraftHelper
     */
    private $draftHelper;

    /**
     * @var array
     */
    private $options;

    /**
     * @param ContextAccessor $contextAccessor
     * @param DraftHelper $draftHelper
     */
    public function __construct(ContextAccessor $contextAccessor, DraftHelper $draftHelper)
    {
        parent::__construct($contextAccessor);
        $this->draftHelper = $draftHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options): ActionInterface
    {
        $this->options = $this->getOptionResolver()->resolve($options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context): void
    {
        $entity = $this->contextAccessor->getValue($context, $this->options[self::OBJECT]);

        if ($entity instanceof DraftableInterface) {
            $this->contextAccessor->setValue(
                $context,
                $this->options[self::ATTRIBUTE],
                $this->draftHelper->getDraftableProperties($entity)
            );
        }
    }

    /**
     * @return OptionsResolver
     */
    private function getOptionResolver(): OptionsResolver
    {
        $optionResolver = new OptionsResolver();
        $optionResolver->setRequired([self::OBJECT, self::ATTRIBUTE]);
        $optionResolver->setAllowedTypes(self::OBJECT, ['object', PropertyPathInterface::class]);
        $optionResolver->setAllowedTypes(self::ATTRIBUTE, ['object', PropertyPathInterface::class]);

        return $optionResolver;
    }
}
