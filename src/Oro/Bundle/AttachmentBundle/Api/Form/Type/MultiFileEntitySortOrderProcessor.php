<?php

namespace Oro\Bundle\AttachmentBundle\Api\Form\Type;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles "sortOrder" option for files in {@see MultiFileEntityType}.
 */
class MultiFileEntitySortOrderProcessor implements MultiFileEntityOptionProcessorInterface
{
    private const string SORT_ORDER = 'sortOrder';

    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function process(FileItem $fileItem, array $dataItem, string $dataItemKey, FormInterface $form): void
    {
        if (!\array_key_exists(self::SORT_ORDER, $dataItem)) {
            $dataItem[self::SORT_ORDER] = $fileItem->getSortOrder();
        }
        $sortOrder = $this->getSortOrder($dataItem[self::SORT_ORDER], $dataItemKey, $form);
        if (null !== $sortOrder) {
            $fileItem->setSortOrder($sortOrder);
        }
    }

    private function getSortOrder(mixed $value, string $dataItemKey, FormInterface $form): ?int
    {
        if (!\is_int($value)) {
            $transformer = new IntegerToLocalizedStringTransformer(locale: 'en');
            try {
                $transformedValue = $transformer->reverseTransform($value);
            } catch (TransformationFailedException) {
                $transformedValue = null;
            }
            if (null === $transformedValue) {
                FormUtil::addNamedFormError(
                    $form,
                    Constraint::TYPE,
                    $this->translator->trans('oro.attachment.sort_order.invalid_type', [], 'validators'),
                    $dataItemKey
                );

                return null;
            }
            $value = $transformedValue;
        }

        if ($value < 0 || $value > 2147483647) {
            FormUtil::addFormConstraintViolation(
                $form,
                new Range(notInRangeMessage: 'oro.attachment.sort_order.not_in_range', min: 0, max: 2147483647),
                $this->translator->trans(
                    'oro.attachment.sort_order.not_in_range',
                    ['{{ min }}' => 0, '{{ max }}' => 2147483647],
                    'validators'
                ),
                $dataItemKey
            );

            return null;
        }

        return $value;
    }
}
