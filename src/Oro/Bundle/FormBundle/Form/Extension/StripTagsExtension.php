<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Strips all HTML tags in submitted data if "strip_tags" option is set.
 */
class StripTagsExtension extends AbstractTypeExtension implements ServiceSubscriberInterface
{
    use FormExtendedTypeTrait;

    const OPTION_NAME = 'strip_tags';

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_ui.html_tag_helper' => HtmlTagHelper::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        if (!empty($options[self::OPTION_NAME])) {
            $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->onPreSubmit());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(self::OPTION_NAME);
    }

    /**
     * @return \Closure
     */
    protected function onPreSubmit()
    {
        return function (FormEvent $event) {
            $data = $event->getData();
            if (is_string($data)) {
                /** @var HtmlTagHelper $htmlTagHelper */
                $htmlTagHelper = $this->container->get('oro_ui.html_tag_helper');
                $event->setData($htmlTagHelper->stripTags($data));
            }
        };
    }
}
