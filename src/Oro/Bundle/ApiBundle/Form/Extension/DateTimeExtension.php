<?php

namespace Oro\Bundle\ApiBundle\Form\Extension;

use Oro\Bundle\ApiBundle\Form\DataTransformer\DateTimeToRfc3339Transformer as Wrapper;
use Oro\Bundle\ApiBundle\Form\DataTransformer\NullValueTransformer;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToRfc3339Transformer;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Wraps "Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToRfc3339Transformer"
 * with "Oro\Bundle\ApiBundle\Form\DataTransformer\DateTimeToRfc3339Transformer"
 * to prevent timezone conversion in "reverseTransform" method for case if the input string contains
 * a date without the time.
 */
class DateTimeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $viewTransformers = $builder->getViewTransformers();
        foreach ($viewTransformers as $key => $viewTransformer) {
            if ($viewTransformer instanceof NullValueTransformer
                && $viewTransformer->getInnerTransformer() instanceof DateTimeToRfc3339Transformer
            ) {
                $viewTransformer->setInnerTransformer(new Wrapper($viewTransformer->getInnerTransformer()));
                break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return DateTimeType::class;
    }
}
