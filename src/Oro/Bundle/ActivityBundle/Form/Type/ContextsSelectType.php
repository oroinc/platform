<?php

namespace Oro\Bundle\ActivityBundle\Form\Type;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActivityBundle\Event\PrepareContextTitleEvent;
use Oro\Bundle\ActivityBundle\Form\DataTransformer\ContextsToViewTransformer;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Form\Type\Select2HiddenType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class ContextsSelectType extends AbstractType
{
    const NAME = 'oro_activity_contexts_select';

    /** @var EntityManager */
    protected $entityManager;

    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var FeatureChecker */
    protected $featureChecker;

    /**
     * @param EntityManager            $entityManager
     * @param ConfigManager            $configManager
     * @param TranslatorInterface      $translator
     * @param EventDispatcherInterface $dispatcher
     * @param EntityNameResolver       $entityNameResolver
     * @param FeatureChecker           $featureChecker
     */
    public function __construct(
        EntityManager $entityManager,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        EventDispatcherInterface $dispatcher,
        EntityNameResolver $entityNameResolver,
        FeatureChecker $featureChecker
    ) {
        $this->entityManager      = $entityManager;
        $this->configManager      = $configManager;
        $this->translator         = $translator;
        $this->dispatcher         = $dispatcher;
        $this->entityNameResolver = $entityNameResolver;
        $this->featureChecker     = $featureChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetViewTransformers();
        $contextsToViewTransformer = new ContextsToViewTransformer(
            $this->entityManager,
            $options['collectionModel']
        );
        $contextsToViewTransformer->setSeparator($options['configs']['separator']);
        $builder->addViewTransformer($contextsToViewTransformer);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['data-selected-data'] = $this->getSelectedData($form, $options['configs']['separator']);
    }

    /**
     * @param FormInterface $form
     * @param string $separator
     *
     * @return string
     */
    protected function getSelectedData(FormInterface $form, $separator)
    {
        $targetEntities = $form->getData();
        if (!$targetEntities) {
            return '';
        }

        $result = [];
        foreach ($targetEntities as $target) {
            $targetClass = ClassUtils::getClass($target);

            $title = $this->entityNameResolver->getName($target);
            if ($label = $this->getClassLabel($targetClass)) {
                $title .= ' (' . $label . ')';
            }

            $item['title'] = $title;
            $item['targetId'] = $target->getId();
            $event = new PrepareContextTitleEvent($item, $targetClass);
            $this->dispatcher->dispatch(PrepareContextTitleEvent::EVENT_NAME, $event);
            $item = $event->getItem();

            $result[] = json_encode($this->getResult($item['title'], $target));
        }

        return implode($separator, $result);
    }

    /**
     * @param string $text
     * @param object $object
     *
     * @return array
     */
    protected function getResult($text, $object)
    {
        $entityClass = ClassUtils::getClass($object);

        return [
            'text' => $text,
            'hidden' => !$this->featureChecker->isResourceEnabled($entityClass, 'entities'),
            /**
             * Selected Value Id should additionally encoded because it should be used as string key
             * to compare with value
             */
            'id'   => json_encode(
                [
                    'entityClass' => $entityClass,
                    'entityId'    => $object->getId(),
                ]
            )
        ];
    }

    /**
     * @param string $className - FQCN
     *
     * @return string|null
     */
    protected function getClassLabel($className)
    {
        if (!$this->configManager->hasConfig($className)) {
            return null;
        }

        $label = $this->configManager->getProvider('entity')->getConfig($className)->get('label');

        return $this->translator->trans($label);
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
            'separator'          => ContextsToViewTransformer::SEPARATOR,
            'forceSelectedData'  => true,
            'minimumInputLength' => 0,
        ];

        $resolver->setDefaults([
            'tooltip' => false,
            'configs' => $defaultConfigs,
            'collectionModel' => false,
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
        return Select2HiddenType::class;
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
