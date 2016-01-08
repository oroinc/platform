<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\QueryDesignerBundle\Model\GroupNode;
use Oro\Component\PhpUtils\ArrayUtil;

class GroupNodeValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value === null) {
            return;
        }

        if (!$this->isValid($value)) {
            $this->context->addViolation($constraint->mixedConditionsMessage);
        }
    }

    /**
     * @param GroupNode $rootNode
     *
     * @return boolean
     */
    protected function isValid(GroupNode $rootNode)
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

        if (in_array(GroupNode::TYPE_MIXED, $types)) {
            return false;
        }

        $computedTypes = ArrayUtil::dropWhile(
            function ($type) {
                return $type === GroupNode::TYPE_UNCOMPUTED;
            },
            $types
        );

        return !in_array(GroupNode::TYPE_UNCOMPUTED, $computedTypes);
    }
}
