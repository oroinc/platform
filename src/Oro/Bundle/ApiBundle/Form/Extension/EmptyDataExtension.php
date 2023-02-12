<?php

namespace Oro\Bundle\ApiBundle\Form\Extension;

use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Util\FormUtil;

/**
 * Unlike default Symfony Forms behaviour, keeps NULL and empty string values as is
 * and uses EntityInstantiator instead of "new" operator to create an instance of data class.
 * Also see the related changes:
 * @see \Oro\Bundle\ApiBundle\Form\DataTransformer\NullValueTransformer
 */
class EmptyDataExtension extends AbstractTypeExtension
{
    private EntityInstantiator $entityInstantiator;

    public function __construct(EntityInstantiator $entityInstantiator)
    {
        $this->entityInstantiator = $entityInstantiator;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dataClass = $options['data_class'];
        $emptyData = $options['empty_data'] ?? null;
        $emptyData = null !== $dataClass
            ? $this->getEmptyDataForDataClass($dataClass, $emptyData)
            : $this->getEmptyData($emptyData);
        $builder->setEmptyData($emptyData);
    }

    /**
     * {@inheritDoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    private function getEmptyData(mixed $emptyData): \Closure
    {
        return function (FormInterface $form, $viewData) use ($emptyData) {
            if ($emptyData instanceof \Closure) {
                $emptyData = $emptyData($form, $viewData);
            }

            if (FormUtil::isEmpty($emptyData)) {
                $emptyData = $viewData;
            }

            return $emptyData;
        };
    }

    private function getEmptyDataForDataClass(mixed $dataClass, mixed $emptyData): \Closure
    {
        return function (FormInterface $form, $viewData) use ($dataClass, $emptyData) {
            $result = null;
            if (!$form->isEmpty() || $form->isRequired()) {
                if ($emptyData instanceof \Closure) {
                    $result = $emptyData($form, $viewData);
                }
                if (null === $result) {
                    $result = $this->entityInstantiator->instantiate($dataClass);
                }
            }

            return $result;
        };
    }
}
