<?php

namespace Oro\Bundle\ActivityBundle\Form\Type;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
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
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Activity Contexts select form type.
 */
class ContextsSelectType extends AbstractType
{
    public function __construct(
        protected ManagerRegistry $doctrine,
        protected ConfigManager $configManager,
        protected TranslatorInterface $translator,
        protected EventDispatcherInterface $dispatcher,
        protected EntityNameResolver $entityNameResolver,
        protected FeatureChecker $featureChecker
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetViewTransformers();
        $contextsToViewTransformer = new ContextsToViewTransformer(
            $this->doctrine,
            $options['collectionModel']
        );
        $contextsToViewTransformer->setSeparator($options['configs']['separator']);
        $builder->addViewTransformer($contextsToViewTransformer);
    }

    #[\Override]
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
            $this->dispatcher->dispatch($event, PrepareContextTitleEvent::EVENT_NAME);
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

        $label = (string) $this->configManager->getProvider('entity')->getConfig($className)->get('label');

        return $this->translator->trans($label);
    }

    #[\Override]
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

    #[\Override]
    public function getParent(): ?string
    {
        return Select2HiddenType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_activity_contexts_select';
    }
}
