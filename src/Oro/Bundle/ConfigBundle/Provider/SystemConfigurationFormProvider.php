<?php

namespace Oro\Bundle\ConfigBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\ConfigBundle\Config\Tree\FieldNodeDefinition;
use Oro\Bundle\ConfigBundle\Config\Tree\GroupNodeDefinition;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class SystemConfigurationFormProvider extends Provider
{
    const TREE_NAME                    = 'system_configuration';
    const CORRECT_FIELDS_NESTING_LEVEL = 5;

    /** @var FormFactoryInterface */
    protected $factory;

    public function __construct($config, FormFactoryInterface $factory, SecurityFacade $securityFacade)
    {
        parent::__construct($config, $securityFacade);

        $this->factory        = $factory;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function getTree()
    {
        return $this->getTreeData(self::TREE_NAME, self::CORRECT_FIELDS_NESTING_LEVEL);
    }

    /**
     * {@inheritdoc}
     */
    public function getForm($group)
    {
        $block = $this->getSubtree($group);

        $toAdd = array();
        $bc    = $block->toBlockConfig();

        if (!$block->isEmpty()) {
            $sbc = array();

            /** @var $subblock GroupNodeDefinition */
            foreach ($block as $subblock) {
                $sbc += $subblock->toBlockConfig();
                if (!$subblock->isEmpty()) {
                    /** @var $field FieldNodeDefinition */
                    foreach ($subblock as $field) {
                        $field->replaceOption('block', $block->getName())
                            ->replaceOption('subblock', $subblock->getName());

                        $toAdd[] = $field;
                    }
                }
            }

            $bc[$block->getName()]['subblocks'] = $sbc;
        }

        $fb = $this->factory->createNamedBuilder($group, 'oro_config_form_type', null, array('block_config' => $bc));
        foreach ($toAdd as $field) {
            $this->addFieldToForm($fb, $field);
        }

        return $fb->getForm();
    }

    /**
     * {@inheritdoc}
     */
    public function chooseActiveGroups($activeGroup, $activeSubGroup)
    {
        $tree = $this->getTree();

        if ($activeGroup === null) {
            $activeGroup = TreeUtils::getFirstNodeName($tree);
        }

        // we can find active subgroup only in case if group is specified
        if ($activeSubGroup === null && $activeGroup) {
            $subtree = TreeUtils::findNodeByName($tree, $activeGroup);

            if ($subtree instanceof GroupNodeDefinition) {
                $subGroups = TreeUtils::getByNestingLevel($subtree, 2);

                if ($subGroups instanceof GroupNodeDefinition) {
                    $activeSubGroup = TreeUtils::getFirstNodeName($subGroups);
                }
            }
        }

        return array($activeGroup, $activeSubGroup);
    }

    /**
     * @param FormBuilderInterface $form
     * @param FieldNodeDefinition  $fieldDefinition
     */
    protected function addFieldToForm(FormBuilderInterface $form, FieldNodeDefinition $fieldDefinition)
    {
        if ($fieldDefinition->getAclResource() && !$this->checkIsGranted($fieldDefinition->getAclResource())) {
            // field is not allowed to be shown, do nothing
            return;
        }

        $name = str_replace(
            ConfigManager::SECTION_MODEL_SEPARATOR,
            ConfigManager::SECTION_VIEW_SEPARATOR,
            $fieldDefinition->getName()
        );

        $form->add($name, 'oro_config_form_field_type', $fieldDefinition->toFormFieldOptions());
    }
}
