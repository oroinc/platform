<?php

namespace Oro\Bundle\CurrencyBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets price based on "value" and "currency" fields if they are submitted.
 * It is expected that an entity for which this processor is used
 * has "getPrice()" and "setPrice(Price $price)" methods.
 */
class UpdatePriceByValueAndCurrency implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        switch ($context->getEvent()) {
            case CustomizeFormDataContext::EVENT_PRE_SUBMIT:
                $this->processPreSubmit($context);
                break;
            case CustomizeFormDataContext::EVENT_POST_VALIDATE:
                FormUtil::fixValidationErrorPropertyPathForExpandedProperty($context->getForm(), 'price');
                break;
        }
    }

    private function processPreSubmit(CustomizeFormDataContext $context): void
    {
        /** @var array $data */
        $data = $context->getData();

        [$value, $isValueSubmitted] = $this->getSubmittedValue($data, $context->findFormFieldName('value'));
        [$currency, $isCurrencySubmitted] = $this->getSubmittedValue($data, $context->findFormFieldName('currency'));

        if ($isValueSubmitted || $isCurrencySubmitted) {
            $entity = $context->getForm()->getData();
            $entityPrice = $entity->getPrice();
            if (null !== $entityPrice) {
                if (!$isValueSubmitted) {
                    $value = $entityPrice->getValue();
                }
                if (!$isCurrencySubmitted) {
                    $currency = $entityPrice->getCurrency();
                }
            }
            $entity->setPrice(Price::create($value, $currency));
        }
    }

    private function getSubmittedValue(array $data, ?string $formFieldName): array
    {
        $value = null;
        $isValueSubmitted = false;
        if (null !== $formFieldName && \array_key_exists($formFieldName, $data)) {
            $value = $data[$formFieldName];
            $isValueSubmitted = true;
        }

        return [$value, $isValueSubmitted];
    }
}
