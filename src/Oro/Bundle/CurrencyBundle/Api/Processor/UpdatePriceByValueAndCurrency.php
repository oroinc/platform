<?php

namespace Oro\Bundle\CurrencyBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\PhpUtils\ReflectionUtil;
use Symfony\Component\Validator\ConstraintViolation;

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
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        switch ($context->getEvent()) {
            case CustomizeFormDataContext::EVENT_PRE_SUBMIT:
                $this->processPreSubmit($context);
                break;
            case CustomizeFormDataContext::EVENT_POST_VALIDATE:
                $this->processPostValidate($context);
                break;
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function processPreSubmit(CustomizeFormDataContext $context): void
    {
        /** @var array $data */
        $data = $context->getData();
        $entity = $context->getForm()->getData();

        $value = null;
        $currency = null;

        $isValueSubmitted = false;
        $valueFieldName = $context->findFormFieldName('value');
        if (null !== $valueFieldName && array_key_exists($valueFieldName, $data)) {
            $isValueSubmitted = true;
            $value = $data[$valueFieldName];
        }

        $isCurrencySubmitted = false;
        $currencyFieldName = $context->findFormFieldName('currency');
        if (null !== $currencyFieldName && array_key_exists($currencyFieldName, $data)) {
            $isCurrencySubmitted = true;
            $currency = $data[$currencyFieldName];
        }

        if ($isValueSubmitted || $isCurrencySubmitted) {
            if (!$isValueSubmitted && null !== $entity->getPrice()) {
                $value = $entity->getPrice()->getValue();
            }
            if (!$isCurrencySubmitted && null !== $entity->getPrice()) {
                $currency = $entity->getPrice()->getCurrency();
            }
            $entity->setPrice(Price::create($value, $currency));
        }
    }

    private function processPostValidate(CustomizeFormDataContext $context): void
    {
        // fix property path for validation errors related to "price" field
        // it is required to return correct error source pointer in API response
        $errors = $context->getForm()->getErrors();
        foreach ($errors as $error) {
            $cause = $error->getCause();
            if ($cause instanceof ConstraintViolation
                && 0 === strpos($cause->getPropertyPath(), 'data.price.')
            ) {
                $property = ReflectionUtil::getProperty(new \ReflectionClass($cause), 'propertyPath');
                if (null !== $property) {
                    $property->setAccessible(true);
                    $property->setValue($cause, str_replace('.price.', '.', $cause->getPropertyPath()));
                }
            }
        }
    }
}
