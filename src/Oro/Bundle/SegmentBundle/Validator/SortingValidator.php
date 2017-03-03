<?php

namespace Oro\Bundle\SegmentBundle\Validator;

use Oro\Bundle\SegmentBundle\Entity\Segment;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SortingValidator extends ConstraintValidator
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param Segment    $segment
     * @param Constraint $constraint
     */
    public function validate($segment, Constraint $constraint)
    {
        if (!$segment instanceof Segment || !$segment->getRecordsLimit()) {
            return;
        }
        /** @var array[] $definition */
        $definition = json_decode($segment->getDefinition(), true);
        foreach ($definition['columns'] as $column) {
            if (!array_key_exists('sorting', $column) || !$column['sorting']) {
                $this->context->addViolation(
                    $this->translator->trans($constraint->message)
                );
            }
        }
    }
}
