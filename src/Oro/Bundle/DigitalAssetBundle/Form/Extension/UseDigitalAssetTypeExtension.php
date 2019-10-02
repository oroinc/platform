<?php

namespace Oro\Bundle\DigitalAssetBundle\Form\Extension;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds availability to use digital asset in file type fields
 */
class UseDigitalAssetTypeExtension extends AbstractTypeExtension
{
    /** @var ConfigManager */
    private $entityConfigManager;

    /**
     * @param ConfigManager $entityConfigManager
     */
    public function __construct(ConfigManager $entityConfigManager)
    {
        $this->entityConfigManager = $entityConfigManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [
            FileType::class,
            ImageType::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'dam_widget_enabled' => true,
            'dam_widget_route' => 'oro_digital_asset_select_widget',
            'dam_widget_parameters' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('dam_file', HiddenType::class, [
            'mapped' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (!array_key_exists('dam_widget', $view->vars) && $options['dam_widget_enabled']) {
            $attachmentConfig = $this->getAttachmentEntityFieldConfig($form);

            if ($attachmentConfig && $attachmentConfig->is('use_dam')) {
                // Adds extra block prefix to render DAM widget.
                array_splice($view->vars['block_prefixes'], -1, 0, ['oro_file_with_digital_asset']);

                $view->vars['dam_widget'] = [
                    'route' => $options['dam_widget_route'],
                    'parameters' => $options['dam_widget_parameters'],
                ];
            }
        }
    }

    /**
     * @param FormInterface $form
     *
     * @return ConfigInterface|null
     */
    private function getAttachmentEntityFieldConfig(FormInterface $form): ?ConfigInterface
    {
        $propertyPath = $form->getPropertyPath();
        if (!$propertyPath || $propertyPath->getLength() !== 1) {
            return null;
        }

        $parentForm = $form->getParent();
        if (!$parentForm) {
            return null;
        }

        $entityClass = $parentForm->getConfig()->getDataClass();
        $fieldName = (string)$propertyPath;
        if (!$entityClass || !$fieldName) {
            return null;
        }

        return $this->entityConfigManager->getFieldConfig('attachment', $entityClass, $fieldName);
    }
}
