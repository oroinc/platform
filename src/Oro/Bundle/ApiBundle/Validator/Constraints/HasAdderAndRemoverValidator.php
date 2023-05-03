<?php

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Oro\Bundle\ApiBundle\Form\ReflectionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * This validator is used to check whether an to-many association has methods to add and to remove elements.
 */
class HasAdderAndRemoverValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof HasAdderAndRemover) {
            throw new UnexpectedTypeException($constraint, HasAdderAndRemover::class);
        }

        if (null === $value) {
            return;
        }

        $methods = ReflectionUtil::findAdderAndRemover($constraint->class, $constraint->property);
        if (!$methods) {
            $pairs = ReflectionUtil::getAdderAndRemoverNames($constraint->property);
            if (1 === \count($pairs)) {
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
                    $pairsText .= sprintf('"%s" and "%s"', $pair[0], $pair[1]);
                }
                $params['{{ methodPairs }}'] = $pairsText;
                $this->context->addViolation($constraint->severalPairsMessage, $params);
            }
        }
    }
}
