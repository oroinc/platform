<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Asset\Packages as AssetHelper;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Bridge\Doctrine\Form\ChoiceList\EntityChoiceList;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\IntegrationBundle\Form\Choice\Loader;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class IntegrationSelectType extends AbstractType
{
    const NAME = 'oro_integration_select';

    /** @var EntityManager */
    protected $em;

    /** @var TypesRegistry */
    protected $typesRegistry;

    /**
     * @param EntityManager $em
     * @param TypesRegistry $typesRegistry
     * @param AssetHelper   $assetHelper
     * @param AclHelper     $aclHelper
     */
    public function __construct(
        EntityManager $em,
        TypesRegistry $typesRegistry,
        AssetHelper $assetHelper,
        AclHelper $aclHelper
    ) {
        $this->em            = $em;
        $this->typesRegistry = $typesRegistry;
        $this->assetHelper   = $assetHelper;
        $this->aclHelper     = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $em             = $this->em;
        $defaultConfigs = [
            'placeholder'             => 'oro.form.choose_value',
            'result_template_twig'    => 'OroIntegrationBundle:Autocomplete:integration/result.html.twig',
            'selection_template_twig' => 'OroIntegrationBundle:Autocomplete:integration/selection.html.twig',
        ];
        // this normalizer allows to add/override config options outside.
        $configsNormalizer = function (Options $options, $configs) use (&$defaultConfigs) {
            return array_merge($defaultConfigs, $configs);
        };
        $choiceList        = function (Options $options) use ($em) {
            $types = $options->has('allowed_types') ? $options->get('allowed_types') : null;

            return new EntityChoiceList(
                $em,
                'OroIntegrationBundle:Channel',
                'name',
                new Loader($this->aclHelper, $em, $types)
            );
        };

        $resolver->setDefaults(
            [
                'empty_value' => '',
                'configs'     => $defaultConfigs,
                'choice_list' => $choiceList
            ]
        );
        $resolver->setOptional(['allowed_types']);
        $resolver->setNormalizers(['configs' => $configsNormalizer]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $typeData = $this->typesRegistry->getAvailableIntegrationTypesDetailedData();

        /** @var ChoiceView $choiceView */
        foreach ($view->vars['choices'] as $choiceView) {
            /** @var Integration $integration */
            $integration = $choiceView->data;
            $attributes  = ['data-status' => $integration->isEnabled()];
            if (isset($typeData[$integration->getType()], $typeData[$integration->getType()]['icon'])) {
                $attributes['data-icon'] = $this->assetHelper->getUrl($typeData[$integration->getType()]['icon']);
            }

            $choiceView->attr = $attributes;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_choice';
    }

    /**
     * {@inheritdoc}
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
