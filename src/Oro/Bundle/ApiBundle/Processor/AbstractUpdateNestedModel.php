<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\PhpUtils\ReflectionUtil;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * An entity that has nested object attributes will usually separate the value and unit into different attributes.
 * (and some attributes have associations, or we can use nestedObject data type in api config.)
 * They integrated into another attribute that has setter/getter for it as an object data type.
 * for example entity ProductShippingOption has weightValue and weightUnit attribute, but access via weight
 * class extends this one could be registered as a processor listener to save data separately.
 */
abstract class AbstractUpdateNestedModel implements ProcessorInterface
{
    protected const SEPARATOR = '.';

    protected string $modelPropertyPath = "";

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
                $this->processPostValidate($context);
                break;
        }
    }

    abstract protected function processPreSubmit(CustomizeFormDataContext $context): void;

    protected function processPostValidate(CustomizeFormDataContext $context): void
    {
        // fix property path for validation errors related to the value/ unit field
        // it is required to return correct error source pointer in API response
        $errors = $context->getForm()->getErrors();

        foreach ($errors as $error) {
            $cause = $error->getCause();
            $propertyPath = sprintf("%s%s%s", self::SEPARATOR, $this->modelPropertyPath, self::SEPARATOR);

            if ($cause instanceof ConstraintViolation
                && str_starts_with($cause->getPropertyPath(), "data" . $propertyPath)
            ) {
                $property = ReflectionUtil::getProperty(new \ReflectionClass($cause), 'propertyPath');
                if (null !== $property) {
                    $property->setAccessible(true);
                    $property->setValue($cause, str_replace($propertyPath, self::SEPARATOR, $cause->getPropertyPath()));
                }
            }
        }
    }
}
