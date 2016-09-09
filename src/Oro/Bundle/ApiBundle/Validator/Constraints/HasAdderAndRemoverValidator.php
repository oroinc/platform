<?php

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use Oro\Bundle\ApiBundle\Form\ReflectionUtil;

class HasAdderAndRemoverValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof HasAdderAndRemover) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\HasAdderAndRemover');
        }

        if (null === $value) {
            return;
        }

        $methods = ReflectionUtil::findAdderAndRemover($constraint->class, $constraint->property);
        if (!$methods) {
            $pairs = ReflectionUtil::getAdderAndRemoverNames($constraint->property);
            if (1 === count($pairs)) {
                $this->context->addViolation(
                    $constraint->message,
                    [
                        '{{ class }}'   => $constraint->class,
                        '{{ adder }}'   => $pairs[0][0],
                        '{{ remover }}' => $pairs[0][1]
                    ]
                );
            } else {
                $pairsText = '';
                $params = [
                    '{{ class }}' => $constraint->class,
                ];
                foreach ($pairs as $index => $pair) {
                    $adderParam = sprintf('{{ adder%d }}', $index + 1);
                    $removerParam = sprintf('{{ remover%d }}', $index + 1);
                    $params[$adderParam] = $pair[0];
                    $params[$removerParam] = $pair[1];
                    if ($pairsText) {
                        $pairsText .= ' or ';
                    }
                    $pairsText .= sprintf('"%s" and "%s"', $adderParam, $removerParam);
                }
                $this->context->addViolation(
                    sprintf($constraint->severalPairsMessage, $pairsText),
                    $params
                );
            }
        }
    }
}
