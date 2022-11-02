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
    /** @var EntityInstantiator */
    private $entityInstantiator;

    public function __construct(EntityInstantiator $entityInstantiator)
    {
        $this->entityInstantiator = $entityInstantiator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dataClass = $options['data_class'];
        $emptyData = $options['empty_data'] ?? null;
        $emptyData = null !== $dataClass
            ? $this->getEmptyDataForDataClass($dataClass, $emptyData)
            : $this->getEmptyData($emptyData);
        $builder->setEmptyData($emptyData);
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    /**
     * @param mixed $emptyData
     *
     * @return \Closure
     */
    private function getEmptyData($emptyData)
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

    /**
     * @param mixed $dataClass
     * @param mixed $emptyData
     *
     * @return \Closure
     */
    private function getEmptyDataForDataClass($dataClass, $emptyData)
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
