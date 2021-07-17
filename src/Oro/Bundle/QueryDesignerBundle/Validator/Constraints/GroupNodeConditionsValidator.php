<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Oro\Bundle\QueryDesignerBundle\Model\GroupNode;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates whether a group node does not contains both computed and uncomputed conditions.
 */
class GroupNodeConditionsValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof GroupNodeConditions) {
            throw new UnexpectedTypeException($constraint, GroupNodeConditions::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof GroupNode) {
            throw new UnexpectedTypeException($value, GroupNode::class);
        }

        if (!$this->isValid($value)) {
            $this->context->addViolation($constraint->message);
        }
    }

    private function isValid(GroupNode $rootNode): bool
    {
        if ($rootNode->getType() !== GroupNode::TYPE_MIXED) {
            return true;
        }

        $types = array_map(
            function ($node) {
                if ($node instanceof GroupNode) {
                    return $node->getType();
                }

                return $node->isComputed() ? GroupNode::TYPE_COMPUTED : GroupNode::TYPE_UNCOMPUTED;
            },
            $rootNode->getChildren()
        );

        if (\in_array(GroupNode::TYPE_MIXED, $types)) {
            return false;
        }

        $computedTypes = ArrayUtil::dropWhile(
            function ($type) {
                return $type === GroupNode::TYPE_UNCOMPUTED;
            },
            $types
        );

        return !\in_array(GroupNode::TYPE_UNCOMPUTED, $computedTypes, true);
    }
}
