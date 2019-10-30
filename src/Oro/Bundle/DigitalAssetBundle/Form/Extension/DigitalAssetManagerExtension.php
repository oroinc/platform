<?php

namespace Oro\Bundle\DigitalAssetBundle\Form\Extension;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Provider\PreviewMetadataProvider;
use Oro\Bundle\DigitalAssetBundle\Reflector\FileReflector;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Enables digital asset manager on file/image form types.
 */
class DigitalAssetManagerExtension extends AbstractTypeExtension implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ConfigManager */
    private $entityConfigManager;

    /** @var EntityClassNameHelper */
    private $entityClassNameHelper;

    /** @var PreviewMetadataProvider */
    private $previewMetadataProvider;

    /** @var EntityToIdTransformer */
    private $digitalAssetToIdTransformer;

    /** @var FileReflector */
    private $fileReflector;

    /**
     * @param ConfigManager $entityConfigManager
     * @param EntityClassNameHelper $entityClassNameHelper
     * @param PreviewMetadataProvider $previewMetadataProvider
     * @param EntityToIdTransformer $digitalAssetToIdTransformer
     * @param FileReflector $fileReflector
     */
    public function __construct(
        ConfigManager $entityConfigManager,
        EntityClassNameHelper $entityClassNameHelper,
        PreviewMetadataProvider $previewMetadataProvider,
        EntityToIdTransformer $digitalAssetToIdTransformer,
        FileReflector $fileReflector
    ) {
        $this->entityConfigManager = $entityConfigManager;
        $this->entityClassNameHelper = $entityClassNameHelper;
        $this->previewMetadataProvider = $previewMetadataProvider;
        $this->digitalAssetToIdTransformer = $digitalAssetToIdTransformer;
        $this->fileReflector = $fileReflector;
        $this->logger = new NullLogger();
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
        ]);
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
            $entityClass = $this->getParentEntityClass($form);
            $fieldName = $this->getParentEntityFieldName($form);

            $attachmentConfig = $this->getAttachmentFieldConfig($entityClass, $fieldName);

            if ($attachmentConfig && $attachmentConfig->is('use_dam')) {
                // Adds extra block prefix to render DAM widget.
                array_splice($view->vars['block_prefixes'], -1, 0, ['oro_file_with_digital_asset']);

                $file = $form->getData();

                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $attachmentConfig->getId();
                $view->vars['dam_widget'] = [
                    'preview_metadata' => $file && $file->getId()
                        ? $this->previewMetadataProvider->getMetadata($file) : [],
                    'is_image_type' => $fieldConfigId->getFieldType() === 'image',
                    'route' => $options['dam_widget_route'],
                    'parameters' => $options['dam_widget_parameters'] ?? [
                            'parentEntityClass' => $this->entityClassNameHelper->getUrlSafeClassName($entityClass),
                            'parentEntityFieldName' => $fieldName,
                        ],
                ];
            }
        }
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     *
     * @return ConfigInterface|null
     */
    private function getAttachmentFieldConfig(string $entityClass, string $fieldName): ?ConfigInterface
    {
        try {
            $attachmentFieldConfig = $this->entityConfigManager->getFieldConfig('attachment', $entityClass, $fieldName);
        } catch (RuntimeException $e) {
            $this->logger
                ->warning(
                    sprintf(
                        'Entity field config for %s entity class and %s field was not found',
                        $entityClass,
                        $fieldName
                    ),
                    ['exception' => $e]
                );

            return null;
        }

        return $attachmentFieldConfig;
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
