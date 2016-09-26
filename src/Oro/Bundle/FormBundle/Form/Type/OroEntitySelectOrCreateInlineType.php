<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityCreationTransformer;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class OroEntitySelectOrCreateInlineType extends AbstractType
{
    const NAME = 'oro_entity_create_or_select_inline';

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ConfigManager */
    protected $configManager;

    /** @var EntityManager */
    protected $entityManager;

    /** @var SearchRegistry */
    protected $searchRegistry;

    /**
     * @param SecurityFacade $securityFacade
     * @param ConfigManager  $configManager
     * @param EntityManager  $entityManager
     * @param SearchRegistry $searchRegistry
     */
    public function __construct(
        SecurityFacade $securityFacade,
        ConfigManager $configManager,
        EntityManager $entityManager,
        SearchRegistry $searchRegistry
    ) {
        $this->securityFacade = $securityFacade;
        $this->configManager  = $configManager;
        $this->entityManager  = $entityManager;
        $this->searchRegistry = $searchRegistry;
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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_jqueryselect2_hidden';
    }

    /**
     * Options:
     * - grid_widget_route - route of widget where selection grid will be rendered
     * - grid_name - name of grid that will be used for entity selection
     * - grid_parameters - parameters need to be passed to grid request
     * - grid_render_parameters - render parameters need to be set for grid rendering
     * - existing_entity_grid_id - grid row field name used as entity identifier
     * - create_enabled - enables new entity creation
     * - create_acl - ACL resource used to determine that create is allowed, by default CREATE for entity used
     * - create_form_route - route name for creation form
     * - create_form_route_parameters - route parameters for create_form_route_parameters
     *
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'existing_entity_grid_id'      => 'id',
                'create_enabled'               => true,
                'create_acl'                   => null,
                'create_form_route'            => null,
                'create_form_route_parameters' => [],
                'grid_widget_route'            => 'oro_datagrid_widget',
                'grid_view_widget_route'       => 'oro_datagrid_widget',
                'grid_name'                    => null,
                'grid_parameters'              => [],
                'grid_render_parameters'       => [],
                'new_item_property_name'        => null,
                'new_item_allow_empty_property' => false,
                'new_item_value_path'           => 'value',
            ]
        );

        $resolver->setNormalizers(
            [
                'create_enabled' => function (Options $options, $createEnabled) {
                    $createRouteName = $options->offsetGet('create_form_route');
                    $createEnabled   = $createEnabled && !empty($createRouteName);
                    if ($createEnabled) {
                        $createEnabled = $this->isCreateGranted($options);
                    }

                    return $createEnabled;
                },
                'grid_name'      => function (Options $options, $gridName) {
                    if (!empty($gridName)) {
                        return $gridName;
                    }

                    $entityClass = $options->offsetGet('entity_class');
                    $formConfig = $this->configManager->getProvider('form')->getConfig($entityClass);
                    if ($formConfig->has('grid_name')) {
                        return $formConfig->get('grid_name');
                    }

                    throw new InvalidConfigurationException(
                        'The option "grid_name" must be set.'
                    );
                },
                'transformer'    => function (Options $options, $value) {
                    if (!$value && !empty($options['entity_class'])) {
                        $value = $this->createDefaultTransformer(
                            $options['entity_class'],
                            $options['new_item_property_name'],
                            $options['new_item_allow_empty_property'],
                            $options['new_item_value_path'],
                            $this->isCreateGranted($options)
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

        $this->setConfigsNormalizer($resolver);
    }

    /**
     * @param Options $options
     *
     * @return bool
     */
    protected function isCreateGranted(Options $options)
    {
        $aclName = $options->offsetGet('create_acl');
        if (empty($aclName)) {
            $aclObjectName = 'Entity:' . $options->offsetGet('entity_class');
            $createEnabled = $this->securityFacade->isGranted('CREATE', $aclObjectName);
        } else {
            $createEnabled = $this->securityFacade->isGranted($aclName);
        }

        return $createEnabled;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    protected function setConfigsNormalizer(OptionsResolverInterface $resolver)
    {
        $resolver->setNormalizers(
            [
                'configs' => function (Options $options, $configs) {
                    if (!empty($options['autocomplete_alias'])) {
                        $autoCompleteAlias            = $options['autocomplete_alias'];
                        $configs['autocomplete_alias'] = $autoCompleteAlias;
                        if (empty($configs['properties'])) {
                            $searchHandler        = $this->searchRegistry->getSearchHandler($autoCompleteAlias);
                            $configs['properties'] = $searchHandler->getProperties();
                        }
                        if (empty($configs['route_name'])) {
                            $configs['route_name'] = 'oro_form_autocomplete_search';
                        }
                        if (empty($configs['component'])) {
                            $configs['component'] = 'autocomplete';
                        }
                    }

                    if (!array_key_exists('route_parameters', $configs)) {
                        $configs['route_parameters'] = [];
                    }

                    if (empty($configs['route_name'])) {
                        throw new InvalidConfigurationException(
                            'Option "configs[route_name]" must be set.'
                        );
                    }

                    if (isset($configs['allowCreateNew']) && $configs['allowCreateNew']) {
                        $configs['allowCreateNew'] = $this->isCreateGranted($options);
                    }

                    return $configs;
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['grid_widget_route']            = $options['grid_widget_route'];
        $view->vars['grid_name']                    = $options['grid_name'];
        $view->vars['grid_parameters']              = $options['grid_parameters'];
        $view->vars['grid_render_parameters']       = $options['grid_render_parameters'];
        $view->vars['existing_entity_grid_id']      = $options['existing_entity_grid_id'];
        $view->vars['create_enabled']               = $options['create_enabled'];
        $view->vars['create_form_route']            = $options['create_form_route'];
        $view->vars['create_form_route_parameters'] = $options['create_form_route_parameters'];
        $view->vars['grid_view_widget_route']       = $options['grid_view_widget_route'];
    }

    /**
     *
     * @param string      $entityClass
     * @param string|null $newItemPropertyName
     * @param bool        $newItemAllowEmptyProperty
     * @param string|null $newItemValuePath
     * @param bool        $isCreateGranted
     *
     * @return EntityToIdTransformer
     */
    public function createDefaultTransformer(
        $entityClass,
        $newItemPropertyName = null,
        $newItemAllowEmptyProperty = false,
        $newItemValuePath = null,
        $isCreateGranted = true
    ) {
        if ($newItemPropertyName && $isCreateGranted) {
            $transformer = new EntityCreationTransformer($this->entityManager, $entityClass);
            $transformer->setNewEntityPropertyName($newItemPropertyName);
            $transformer->setAllowEmptyProperty($newItemAllowEmptyProperty);
            $transformer->setValuePath($newItemValuePath);
        } else {
            $transformer = new EntityToIdTransformer($this->entityManager, $entityClass);
        }

        return $transformer;
    }
}
