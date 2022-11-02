<?php

namespace Oro\Bundle\UIBundle\Validator\Constraints;

use Oro\Bundle\UIBundle\Model\TreeCollection;
use Oro\Bundle\UIBundle\Model\TreeItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates that a node can be moved to another node.
 */
class MoveToChildValidator extends ConstraintValidator
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param TreeCollection $collection
     * @param MoveToChild    $constraint
     *
     * {@inheritdoc}
     */
    public function validate($collection, Constraint $constraint)
    {
        if (!$collection instanceof TreeCollection) {
            return;
        }

        foreach ($collection->source as $source) {
            $item = $this->findChildRecursive($source, $collection->target->getKey());
            if ($item) {
                $itemLabel = $this->translator->trans((string) $source->getLabel());
                $targetLabel = $this->translator->trans((string) $collection->target->getLabel());
                $this->context->addViolation(sprintf(
                    'Can\'t move node "%s" to "%s". Node "%s" is a child of "%s" already.',
                    $itemLabel,
                    $targetLabel,
                    $targetLabel,
                    $itemLabel
                ));
            }
        }
    }

    /**
     * @param TreeItem $item
     * @param string   $key
     *
     * @return null|TreeItem
     */
    private function findChildRecursive(TreeItem $item, $key)
    {
        if ($item->getKey() === $key) {
            return $item;
        }

        $children = $item->getChildren();

        if (count($children) !== 0) {
            foreach ($children as $child) {
                $child = $this->findChildRecursive($child, $key);
                if ($child !== null) {
                    return $child;
                }
            }
        }

        return null;
    }
}
