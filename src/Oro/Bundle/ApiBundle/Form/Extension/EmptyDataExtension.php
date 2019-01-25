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

    /**
     * @param EntityInstantiator $entityInstantiator
     */
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
        $emptyData = null !== $dataClass
            ? $this->getEmptyDataForDataClass($dataClass)
            : $this->getEmptyData($options['empty_data']);
        $builder->setEmptyData($emptyData);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FormType::class;
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
     *
     * @return \Closure
     */
    private function getEmptyDataForDataClass($dataClass)
    {
        return function (FormInterface $form) use ($dataClass) {
            return $form->isEmpty() && !$form->isRequired()
                ? null
                : $this->entityInstantiator->instantiate($dataClass);
        };
    }
}
