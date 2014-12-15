<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class OroDateTimeType extends AbstractType
{
    const NAME = 'oro_datetime';

    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @var OroDateType
     */
    protected $dateType;

    /**
     * @param LocaleSettings $localeSettings
     * @param OroDateType $dateType
     */
    public function __construct(LocaleSettings $localeSettings, OroDateType $dateType)
    {
        $this->localeSettings = $localeSettings;
        $this->dateType = $dateType;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $this->dateType->finishView($view, $form, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->dateType->setDefaultOptions($resolver);

        $resolver->setDefaults(['format' => DateTimeType::HTML5_FORMAT]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'datetime';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
