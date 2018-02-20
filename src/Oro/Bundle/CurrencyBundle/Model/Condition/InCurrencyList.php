<?php
namespace Oro\Bundle\CurrencyBundle\Model\Condition;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyListProviderInterface;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;

class InCurrencyList extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'in_currency_list';

    /** @var MultiCurrency */
    protected $entity;

    /** @var CurrencyListProviderInterface  */
    protected $currencyProvider;

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * @param CurrencyListProviderInterface $currencyProvider
     */
    public function __construct(CurrencyListProviderInterface $currencyProvider)
    {
        $this->currencyProvider = $currencyProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $entity = $this->resolveValue($context, $this->entity);

        if (!$entity instanceof MultiCurrency) {
            throw new Exception\InvalidArgumentException(
                sprintf('Entity must be object of %s class', MultiCurrency::class)
            );
        }
        $currencies = $this->currencyProvider->getCurrencyList();
        return in_array($entity->getCurrency(), $currencies);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (count($options) !== 1) {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 1 element, but %d given.', count($options))
            );
        }

        if (isset($options['entity'])) {
            $this->entity = $options['entity'];
        } elseif (isset($options[0])) {
            $this->entity = $options[0];
        } else {
            throw new Exception\InvalidArgumentException('Option "entity" must be set.');
        }
    }
}
