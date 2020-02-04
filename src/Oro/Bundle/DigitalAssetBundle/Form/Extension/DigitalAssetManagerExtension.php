<?php

namespace Oro\Bundle\DigitalAssetBundle\Form\Extension;

use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\AttachmentBundle\Helper\FieldConfigHelper;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProviderInterface;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Provider\PreviewMetadataProvider;
use Oro\Bundle\DigitalAssetBundle\Reflector\FileReflector;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Enables digital asset manager on file/image form types.
 */
class DigitalAssetManagerExtension extends AbstractTypeExtension
{
    /** @var AttachmentEntityConfigProviderInterface */
    private $attachmentEntityConfigProvider;

    /** @var EntityClassNameHelper */
    private $entityClassNameHelper;

    /** @var PreviewMetadataProvider */
    private $previewMetadataProvider;

    /** @var EntityToIdTransformer */
    private $digitalAssetToIdTransformer;

    /** @var FileReflector */
    private $fileReflector;

    /**
     * @param AttachmentEntityConfigProviderInterface $attachmentEntityConfigProvider
     * @param EntityClassNameHelper $entityClassNameHelper
     * @param PreviewMetadataProvider $previewMetadataProvider
     * @param EntityToIdTransformer $digitalAssetToIdTransformer
     * @param FileReflector $fileReflector
     */
    public function __construct(
        AttachmentEntityConfigProviderInterface $attachmentEntityConfigProvider,
        EntityClassNameHelper $entityClassNameHelper,
        PreviewMetadataProvider $previewMetadataProvider,
        EntityToIdTransformer $digitalAssetToIdTransformer,
        FileReflector $fileReflector
    ) {
        $this->attachmentEntityConfigProvider = $attachmentEntityConfigProvider;
        $this->entityClassNameHelper = $entityClassNameHelper;
        $this->previewMetadataProvider = $previewMetadataProvider;
        $this->digitalAssetToIdTransformer = $digitalAssetToIdTransformer;
        $this->fileReflector = $fileReflector;
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
            'dam_widget_route' => 'oro_digital_asset_widget_choose',
            'dam_widget_parameters' => null,
            'validation_groups' => [$this, 'validationGroupsCallback'],
        ]);

        $resolver->setNormalizer('fileOptions', \Closure::fromCallable([$this, 'normalizeFileOptions']));
    }

    /**
     * @param FormInterface $form
     *
     * @return array
     */
    public function validationGroupsCallback(FormInterface $form): array
    {
        $groups = ['Default'];
        $options = $form->getConfig()->getOptions();
        if ($options['checkEmptyFile']) {
            if (!$options['dam_widget_enabled']
                || !($attachmentConfig = $this->getAttachmentConfig($form))
                || !$attachmentConfig->is('use_dam')) {
                $groups[] = 'DamWidgetDisabled';
            } else {
                $groups[] = 'DamWidgetEnabled';
            }
        }

        return $groups;
    }

    /**
     * @param Options $allOptions
     * @param array $option
     *
     * @return array
     */
    public function normalizeFileOptions(Options $allOptions, array $option): array
    {
        if (!array_key_exists('required', $option)) {
            $option['required'] = $allOptions['checkEmptyFile'];
        }

        if (!array_key_exists('constraints', $option) && $allOptions['checkEmptyFile']) {
            $option['constraints'] = [new NotBlank(['groups' => 'DamWidgetDisabled'])];
        }

        if (!array_key_exists('label', $option)) {
            $option['label'] = 'oro.attachment.file.label';
        }

        return $option;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'digitalAsset',
            HiddenType::class,
            [
                'error_bubbling' => false,
                'invalid_message' => 'oro.digitalasset.validator.digital_asset.invalid',
                'auto_initialize' => false,
                'constraints' => [new NotBlank(['groups' => 'DamWidgetEnabled'])],
            ]
        );
        $builder->get('digitalAsset')->addModelTransformer($this->digitalAssetToIdTransformer);

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    /**
     * Populates file with properties from source file of the chosen digital asset.
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event): void
    {
        $file = $event->getData();

        if (!$file) {
            return;
        }

        /** @var DigitalAsset|null $digitalAsset */
        $digitalAsset = $file->getDigitalAsset();
        if (!$digitalAsset) {
            return;
        }

        $this->fileReflector->reflectFromDigitalAsset($file, $digitalAsset);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (!array_key_exists('dam_widget', $view->vars) && $options['dam_widget_enabled']) {
            $attachmentConfig = $this->getAttachmentConfig($form);

            if ($attachmentConfig && $attachmentConfig->is('use_dam')) {
                /** @var FieldConfigId $configId */
                $configId = $attachmentConfig->getId();

                // Adds extra block prefix to render DAM widget.
                array_splice($view->vars['block_prefixes'], -1, 0, ['oro_file_with_digital_asset']);

                $file = $form->getData();

                $view->vars['dam_widget'] = [
                    'is_valid_digital_asset' => $form->isSubmitted() ? $form->get('digitalAsset')->isValid() : true,
                    'preview_metadata' => $file
                        ? $this->previewMetadataProvider->getMetadata($file) : [],
                    'is_image_type' => FieldConfigHelper::isImageField($configId),
                    'route' => $options['dam_widget_route'],
                    'parameters' => $options['dam_widget_parameters'] ?? [
                            'parentEntityClass' => $this->entityClassNameHelper->getUrlSafeClassName(
                                $configId->getClassName()
                            ),
                            'parentEntityFieldName' => $configId->getFieldName(),
                        ],
                ];
            }
        }
    }

    /**
     * @param FormInterface $form
     *
     * @return ConfigInterface|null
     */
    private function getAttachmentConfig(FormInterface $form): ?ConfigInterface
    {
        $entityClass = $this->getParentEntityClass($form);
        $fieldName = $this->getParentEntityFieldName($form);

        if ($entityClass === FileItem::class) {
            $entityClass = $this->getParentEntityClass($form->getParent()->getParent());
            $fieldName = $this->getParentEntityFieldName($form->getParent()->getParent());
        }

        return $this->attachmentEntityConfigProvider->getFieldConfig($entityClass, $fieldName);
    }

    /**
     * @param FormInterface $form
     *
     * @return string
     */
    private function getParentEntityFieldName(FormInterface $form): string
    {
        $propertyPath = $form->getPropertyPath();
        if (!$propertyPath || $propertyPath->getLength() !== 1) {
            return '';
        }

        return (string)$propertyPath;
    }

    /**
     * @param FormInterface $form
     *
     * @return string
     */
    private function getParentEntityClass(FormInterface $form): string
    {
        $parentForm = $form->getParent();
        if (!$parentForm) {
            return '';
        }

        return (string)$parentForm->getConfig()->getDataClass();
    }
}
