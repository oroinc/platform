<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToRfc3339Transformer;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reverts https://github.com/symfony/symfony/pull/24401 to avoid BC break.
 * Also the default format is changed in OroDateTimeType
 * @see \Oro\Bundle\FormBundle\Form\Type\OroDateTimeType::setDefaultOptions
 */
class DateTimeExtension extends AbstractTypeExtension
{
    const HTML5_FORMAT_WITHOUT_TIMEZONE = "yyyy-MM-dd'T'HH:mm:ss";
    const HTML5_FORMAT_WITH_TIMEZONE = "yyyy-MM-dd'T'HH:mm:ssZZZZZ";

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['format' => self::HTML5_FORMAT_WITH_TIMEZONE]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $pattern = is_string($options['format']) ? $options['format'] : null;
        if (self::HTML5_FORMAT_WITH_TIMEZONE === $pattern) {
            $this->replaceLocalizedStringWithRfc3339ViewTransformer($builder, $options);
        } elseif (self::HTML5_FORMAT_WITHOUT_TIMEZONE === $pattern) {
            $this->replaceRfc3339WithLocalizedStringViewTransformer($builder, $pattern, $options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (array_key_exists('type', $view->vars) && 'datetime-local' === $view->vars['type']) {
            $view->vars['type'] = 'datetime';
        } elseif ($options['html5']
            && 'single_text' === $options['widget']
            && self::HTML5_FORMAT_WITH_TIMEZONE === $options['format']
        ) {
            $view->vars['type'] = 'datetime';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return DateTimeType::class;
    }

    /**
     * Replaces DateTimeToLocalizedStringTransformer with DateTimeToRfc3339Transformer view transformer.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    private function replaceLocalizedStringWithRfc3339ViewTransformer(
        FormBuilderInterface $builder,
        array $options
    ) {
        $transformerKey = null;
        $viewTransformers = $builder->getViewTransformers();
        foreach ($viewTransformers as $key => $viewTransformer) {
            if ($viewTransformer instanceof DateTimeToLocalizedStringTransformer) {
                $transformerKey = $key;
                break;
            }
        }
        if (null !== $transformerKey) {
            $builder->resetViewTransformers();
            $viewTransformers[$transformerKey] = new DateTimeToRfc3339Transformer(
                $options['model_timezone'],
                $options['view_timezone']
            );
            \rsort($viewTransformers);
            foreach ($viewTransformers as $key => $viewTransformer) {
                $builder->addViewTransformer($viewTransformer);
            }
        }
    }

    /**
     * Replaces DateTimeToRfc3339Transformer with DateTimeToLocalizedStringTransformer view transformer.
     *
     * @param FormBuilderInterface $builder
     * @param string               $pattern
     * @param array                $options
     */
    private function replaceRfc3339WithLocalizedStringViewTransformer(
        FormBuilderInterface $builder,
        $pattern,
        array $options
    ) {
        $transformerKey = null;
        $viewTransformers = $builder->getViewTransformers();
        foreach ($viewTransformers as $key => $viewTransformer) {
            if ($viewTransformer instanceof DateTimeToRfc3339Transformer) {
                $transformerKey = $key;
                break;
            }
        }
        if (null !== $transformerKey) {
            $builder->resetViewTransformers();
            $viewTransformers[$transformerKey] = new DateTimeToLocalizedStringTransformer(
                $options['model_timezone'],
                $options['view_timezone'],
                is_int($options['date_format']) ? $options['date_format'] : DateTimeType::DEFAULT_DATE_FORMAT,
                DateTimeType::DEFAULT_TIME_FORMAT,
                \IntlDateFormatter::GREGORIAN,
                $pattern
            );
            \rsort($viewTransformers);
            foreach ($viewTransformers as $key => $viewTransformer) {
                $builder->addViewTransformer($viewTransformer);
            }
        }
    }
}
