<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\DataTransformer\RemoveMillisecondsFromDateTimeTransformer;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToHtml5LocalDateTimeTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reverts https://github.com/symfony/symfony/pull/24401 to avoid BC break,
 * https://github.com/symfony/symfony/pull/28466 and https://github.com/symfony/symfony/pull/28372 as well.
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
            // old REST API should accept values with milliseconds,
            // like "2014-03-04T20:00:00.123Z" or "2014-03-04T20:00:00.123+01:00"
            $this->wrapLocalizedStringWithRemoveMillisecondsTransformer($builder);
        } elseif (self::HTML5_FORMAT_WITHOUT_TIMEZONE === $pattern) {
            $this->replaceHtml5LocalDateTimeWithLocalizedStringViewTransformer($builder, $pattern, $options);
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
     * Wraps DateTimeToLocalizedStringTransformer with RemoveMillisecondsFromDateTimeTransformer view transformer.
     *
     * @param FormBuilderInterface $builder
     */
    private function wrapLocalizedStringWithRemoveMillisecondsTransformer(FormBuilderInterface $builder)
    {
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
            $viewTransformers[$transformerKey] = new RemoveMillisecondsFromDateTimeTransformer(
                $viewTransformers[$transformerKey]
            );
            \rsort($viewTransformers);
            foreach ($viewTransformers as $key => $viewTransformer) {
                $builder->addViewTransformer($viewTransformer);
            }
        }
    }

    /**
     * Replaces DateTimeToHtml5LocalDateTimeTransformer with DateTimeToLocalizedStringTransformer view transformer.
     *
     * @param FormBuilderInterface $builder
     * @param string               $pattern
     * @param array                $options
     */
    private function replaceHtml5LocalDateTimeWithLocalizedStringViewTransformer(
        FormBuilderInterface $builder,
        $pattern,
        array $options
    ) {
        $transformerKey = null;
        $viewTransformers = $builder->getViewTransformers();
        foreach ($viewTransformers as $key => $viewTransformer) {
            if ($viewTransformer instanceof DateTimeToHtml5LocalDateTimeTransformer) {
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
