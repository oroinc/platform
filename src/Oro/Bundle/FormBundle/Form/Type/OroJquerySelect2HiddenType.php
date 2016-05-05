<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityCreationTransformer;

class OroJquerySelect2HiddenType extends AbstractType
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var SearchRegistry
     */
    protected $searchRegistry;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @param EntityManager  $entityManager
     * @param SearchRegistry $registry
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        EntityManager $entityManager,
        SearchRegistry $registry,
        ConfigProvider $configProvider
    ) {
        $this->entityManager  = $entityManager;
        $this->searchRegistry = $registry;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $defaultConfig = [
            'placeholder'        => 'oro.form.choose_value',
            'allowClear'         => true,
            'minimumInputLength' => 0,
        ];

        $resolver
            ->setDefaults(
                [
                    'empty_value'                   => '',
                    'empty_data'                    => null,
                    'data_class'                    => null,
                    'entity_class'                  => null,
                    'configs'                       => $defaultConfig,
                    'converter'                     => null,
                    'autocomplete_alias'            => null,
                    'excluded'                      => null,
                    'random_id'                     => true,
                    'error_bubbling'                => false,
                    'new_item_property_name'        => null,
                    'new_item_allow_empty_property' => false,
                    'new_item_value_path'           => 'value',
                ]
            );

        $this->setConverterNormalizer($resolver);
        $this->setConfigsNormalizer($resolver, $defaultConfig);

        $resolver
            ->setNormalizers(
                [
                    'entity_class' => function (Options $options, $entityClass) {
                        if (!empty($entityClass)) {
                            return $entityClass;
                        }

                        if (!empty($options['autocomplete_alias'])) {
                            $searchHandler = $this->searchRegistry->getSearchHandler($options['autocomplete_alias']);

                            return $searchHandler->getEntityName();
                        }

                        throw new InvalidConfigurationException('The option "entity_class" must be set.');
                    },
                    'transformer'  => function (Options $options, $value) {
                        if (!$value && !empty($options['entity_class'])) {
                            $value = $this->createDefaultTransformer(
                                $options['entity_class'],
                                $options['new_item_property_name'],
                                $options['new_item_allow_empty_property'],
                                $options['new_item_value_path']
                            );
                        }

                        if (!$value instanceof DataTransformerInterface) {
                            throw new TransformationFailedException(
                                sprintf(
                                    'The option "transformer" must be an instance of "%s".',
                                    'Symfony\Component\Form\DataTransformerInterface'
                                )
                            );
                        }

                        return $value;
                    }
                ]
            );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    protected function setConverterNormalizer(OptionsResolverInterface $resolver)
    {
        $resolver->setNormalizers(
            [
                'converter' => function (Options $options, $value) {
                    if (!$value && !empty($options['autocomplete_alias'])) {
                        $value = $this->searchRegistry->getSearchHandler($options['autocomplete_alias']);
                    }

                    if (!$value) {
                        throw new InvalidConfigurationException('The option "converter" must be set.');
                    }

                    if (!$value instanceof ConverterInterface) {
                        throw new UnexpectedTypeException(
                            $value,
                            'Oro\Bundle\FormBundle\Autocomplete\ConverterInterface'
                        );
                    }

                    return $value;
                }
            ]
        );
    }

    /**
     * @param OptionsResolverInterface $resolver
     * @param array                    $defaultConfig
     */
    protected function setConfigsNormalizer(OptionsResolverInterface $resolver, array $defaultConfig)
    {
        $resolver->setNormalizers(
            [
                'configs' => function (Options $options, $configs) use ($defaultConfig) {
                    $result = array_replace_recursive($defaultConfig, $configs);

                    if (!empty($options['autocomplete_alias'])) {
                        $autoCompleteAlias            = $options['autocomplete_alias'];
                        $result['autocomplete_alias'] = $autoCompleteAlias;
                        if (empty($result['properties'])) {
                            $searchHandler        = $this->searchRegistry->getSearchHandler($autoCompleteAlias);
                            $result['properties'] = $searchHandler->getProperties();
                        }
                        if (empty($result['route_name'])) {
                            $result['route_name'] = 'oro_form_autocomplete_search';
                        }
                        if (empty($result['component'])) {
                            $result['component'] = 'autocomplete';
                        }
                    }

                    if (!array_key_exists('route_parameters', $result)) {
                        $result['route_parameters'] = [];
                    }

                    if (empty($result['route_name'])) {
                        throw new InvalidConfigurationException(
                            'Option "configs[route_name]" must be set.'
                        );
                    }

                    return $result;
                }
            ]
        );
    }

    /**
     *
     * @param string      $entityClass
     * @param string|null $newItemPropertyName
     * @param bool        $newItemAllowEmptyProperty
     * @param string|null $newItemValuePath
     *
     * @return EntityToIdTransformer
     */
    public function createDefaultTransformer(
        $entityClass,
        $newItemPropertyName = null,
        $newItemAllowEmptyProperty = false,
        $newItemValuePath = null
    ) {
        if ($newItemPropertyName) {
            $transformer = new EntityCreationTransformer($this->entityManager, $entityClass);
            $transformer->setNewEntityPropertyName($newItemPropertyName);
            $transformer->setAllowEmptyProperty($newItemAllowEmptyProperty);
            $transformer->setValuePath($newItemValuePath);
        } else {
            $transformer = new EntityToIdTransformer($this->entityManager, $entityClass);
        }

        return $transformer;
    }

    /**
     * Set data-title attribute to element to show selected value
     *
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $vars = [
            'configs'  => $options['configs'],
            'excluded' => (array)$options['excluded']
        ];

        if ($form->getData()) {
            $result = [];
            /** @var ConverterInterface $converter */
            $converter = $options['converter'];
            if (isset($options['configs']['multiple']) && $options['configs']['multiple']) {
                foreach ($form->getData() as $item) {
                    $result[] = $converter->convertItem($item);
                }
            } else {
                $result[] = $converter->convertItem($form->getData());
            }

            $vars['attr'] = [
                'data-selected-data' => json_encode($result)
            ];
        }

        $view->vars = array_replace_recursive($view->vars, $vars);
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
        return 'oro_jqueryselect2_hidden';
    }
}
