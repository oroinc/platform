<?php

namespace Oro\Bundle\ActivityBundle\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActivityBundle\Form\DataTransformer\ContextsToViewTransformer;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Resolver\EntityTitleResolverInterface;

class ContextsSelectType extends AbstractType
{
    const NAME = 'oro_activity_contexts_select';

    /** @var EntityManager */
    protected $entityManager;

    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /* @var TokenStorageInterface */
    protected $securityTokenStorage;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var EntityTitleResolverInterface */
    protected $entityTitleResolver;

    /**
     * @param EntityManager         $entityManager
     * @param ConfigManager         $configManager
     * @param TranslatorInterface   $translator
     * @param TokenStorageInterface $securityTokenStorage
     * @param EventDispatcherInterface $dispatcher
     * @param EntityTitleResolverInterface $entityTitleResolver
     */
    public function __construct(
        EntityManager $entityManager,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        TokenStorageInterface $securityTokenStorage,
        EventDispatcherInterface $dispatcher,
        EntityTitleResolverInterface $entityTitleResolver
    ) {
        $this->entityManager        = $entityManager;
        $this->configManager        = $configManager;
        $this->translator           = $translator;
        $this->securityTokenStorage = $securityTokenStorage;
        $this->dispatcher           = $dispatcher;
        $this->entityTitleResolver  = $entityTitleResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetViewTransformers();
        $builder->addViewTransformer(
            new ContextsToViewTransformer(
                $this->entityManager,
                $this->configManager,
                $this->translator,
                $this->securityTokenStorage,
                $this->dispatcher,
                $this->entityTitleResolver
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $formData = $form->getViewData();

        $view->vars['attr']['data-selected-data'] = $formData;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $defaultConfigs = [
            'placeholder'        => 'oro.activity.contexts.placeholder',
            'allowClear'         => true,
            'multiple'           => true,
            'separator'          => ';',
            'forceSelectedData'  => true,
            'minimumInputLength' => 0,
        ];

        $resolver->setDefaults([
            'tooltip' => false,
            'configs' => $defaultConfigs
        ]);

        $resolver->setNormalizer(
            'configs',
            function (Options $options, $configs) use ($defaultConfigs) {
                return array_replace_recursive($defaultConfigs, $configs);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
