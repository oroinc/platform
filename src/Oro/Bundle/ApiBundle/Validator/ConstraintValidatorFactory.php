<?php

namespace Oro\Bundle\ApiBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Bundle\FrameworkBundle\Validator\ConstraintValidatorFactory as BaseConstraintValidatorFactory;

use Oro\Bundle\ApiBundle\Form\FormExtensionCheckerInterface;

/**
 * The constraint validators factory that allows to replace validators used in API forms.
 *
 * We have to use inheritance instead of decoration because
 * Symfony\Component\DependencyInjection\Compiler\DecoratorServicePass is executed
 * before Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\AddConstraintValidatorsPass
 * and, as result, validators are not registered in the factory at all.
 */
class ConstraintValidatorFactory extends BaseConstraintValidatorFactory
{
    /** @var FormExtensionCheckerInterface */
    private $formExtensionChecker;

    /** @var array [old validator class name => new validator class name or validator instance, ...] */
    private $replacements = [];

    /**
     * @param FormExtensionCheckerInterface $formExtensionChecker
     */
    public function setFormExtensionChecker(FormExtensionCheckerInterface $formExtensionChecker)
    {
        $this->formExtensionChecker = $formExtensionChecker;
    }

    /**
     * @param string $oldClass
     * @param string $newClass
     */
    public function replaceValidatorClass($oldClass, $newClass)
    {
        $this->replacements[$oldClass] = $newClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getInstance(Constraint $constraint)
    {
        if ($this->formExtensionChecker->isApiFormExtensionActivated()) {
            $name = $constraint->validatedBy();
            if (isset($this->replacements[$name])) {
                $validator = $this->replacements[$name];
                if (is_string($validator)) {
                    if (!class_exists($validator)) {
                        throw new ValidatorException(
                            sprintf('Constraint validator "%s" does not exist.', $validator)
                        );
                    }
                    $validator = new $validator();
                    $this->replacements[$name] = $validator;
                }
                if (!$validator instanceof ConstraintValidatorInterface) {
                    throw new UnexpectedTypeException($validator, ConstraintValidatorInterface::class);
                }

                return $validator;
            }
        }

        return parent::getInstance($constraint);
    }
}
