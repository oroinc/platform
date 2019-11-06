<?php

namespace Oro\Bundle\DigitalAssetBundle\Controller;

use Oro\Bundle\AttachmentBundle\Provider\FileConstraintsProvider;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Form\Type\DigitalAssetInDialogType;
use Oro\Bundle\DigitalAssetBundle\Form\Type\DigitalAssetType;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller for DigitalAsset entity.
 */
class DigitalAssetController extends AbstractController
{
    /**
     * @Route("/", name="oro_digital_asset_index")
     * @Template("@OroDigitalAsset/DigitalAsset/index.html.twig")
     * @AclAncestor("oro_digital_asset_view")
     *
     * @return array
     */
    public function indexAction(): array
    {
        return [
            'entity_class' => DigitalAsset::class,
        ];
    }

    /**
     * @Route("/create", name="oro_digital_asset_create")
     * @Template("@OroDigitalAsset/DigitalAsset/update.html.twig")
     * @AclAncestor("oro_digital_asset_create")
     *
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        return $this->update(new DigitalAsset());
    }

    /**
     * @Route("/update/{id}", name="oro_digital_asset_update", requirements={"id"="\d+"})
     * @Template("@OroDigitalAsset/DigitalAsset/update.html.twig")
     * @AclAncestor("oro_digital_asset_update")
     *
     * @param DigitalAsset $digitalAsset
     *
     * @return array|RedirectResponse
     */
    public function updateAction(DigitalAsset $digitalAsset)
    {
        return $this->update($digitalAsset);
    }

    /**
     * @param DigitalAsset $digitalAsset
     *
     * @return array|RedirectResponse
     */
    protected function update(DigitalAsset $digitalAsset)
    {
        return $this->get(UpdateHandlerFacade::class)
            ->update(
                $digitalAsset,
                $this->createForm(DigitalAssetType::class, $digitalAsset),
                $this->get(TranslatorInterface::class)->trans('oro.digitalasset.controller.saved.message')
            );
    }

    /**
     * @Route("/widget/choose/{parentEntityClass}/{parentEntityFieldName}", name="oro_digital_asset_widget_choose")
     * @Template("@OroDigitalAsset/DigitalAsset/widget/choose.html.twig")
     * @AclAncestor("oro_digital_asset_create")
     *
     * @param string $parentEntityClass
     * @param string $parentEntityFieldName
     *
     * @return array|RedirectResponse
     */
    public function chooseAction(string $parentEntityClass, string $parentEntityFieldName)
    {
        try {
            $resolvedParentEntityClass = $this->get(EntityClassNameHelper::class)
                ->resolveEntityClass($parentEntityClass);
        } catch (EntityAliasNotFoundException $e) {
            $this->get(LoggerInterface::class)
                ->warning(sprintf('Entity alias for %s was not found', $parentEntityClass), ['exception' => $e]);

            throw new NotFoundHttpException();
        }

        $attachmentEntityFieldConfig = $this
            ->getAttachmentEntityFieldConfig($resolvedParentEntityClass, $parentEntityFieldName);

        if (!$attachmentEntityFieldConfig) {
            throw new NotFoundHttpException();
        }

        $isImageType = $this->isImageType($attachmentEntityFieldConfig);
        $mimeTypes = $this->get(FileConstraintsProvider::class)
            ->getAllowedMimeTypesForEntityField($resolvedParentEntityClass, $parentEntityFieldName);
        $maxFileSize = $this->get(FileConstraintsProvider::class)
            ->getMaxSizeForEntityField($resolvedParentEntityClass, $parentEntityFieldName);

        return $this->handleChooseForm($isImageType, $mimeTypes, $maxFileSize);
    }

    /**
     * @param bool $isImageType
     * @param array $mimeTypes
     * @param int $maxFileSize
     *
     * @return array|RedirectResponse
     */
    protected function handleChooseForm(bool $isImageType, array $mimeTypes, int $maxFileSize)
    {
        $form = $this->createForm(
            DigitalAssetInDialogType::class,
            new DigitalAsset(),
            [
                'is_image_type' => $isImageType,
                'mime_types' => $mimeTypes,
                'max_file_size' => $maxFileSize,
            ]
        );

        return $this->get(UpdateHandlerFacade::class)
            ->update(
                $form->getData(),
                $form,
                '',
                null,
                null,
                function ($entity, FormInterface $form, Request $request) use ($mimeTypes, $maxFileSize, $isImageType) {
                    return [
                        'saved' => $form->isSubmitted() && $form->isValid(),
                        'digital_asset' => $entity,
                        'is_image_type' => $isImageType,
                        'grid_name' => $isImageType
                            ? 'digital-asset-select-image-grid'
                            : 'digital-asset-select-file-grid',
                        'grid_params' => ['mime_types' => $mimeTypes, 'max_file_size' => $maxFileSize],
                        'form' => $form->createView(),
                    ];
                }
            );
    }

    /**
     * @param ConfigInterface $entityFieldConfig
     *
     * @return bool
     */
    protected function isImageType(ConfigInterface $entityFieldConfig): bool
    {
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $entityFieldConfig->getId();

        return $fieldConfigId->getFieldType() === 'image';
    }

    /**
     * @param string $parentEntityClass
     * @param string $parentEntityFieldName
     *
     * @return ConfigInterface|null
     */
    protected function getAttachmentEntityFieldConfig(
        string $parentEntityClass,
        string $parentEntityFieldName
    ): ?ConfigInterface {
        try {
            $attachmentEntityFieldConfig = $this->get(EntityConfigManager::class)
                ->getFieldConfig('attachment', $parentEntityClass, $parentEntityFieldName);
        } catch (RuntimeException $e) {
            $this->get(LoggerInterface::class)
                ->warning(
                    sprintf(
                        'Entity field config for %s entity class and %s field was not found',
                        $parentEntityClass,
                        $parentEntityFieldName
                    ),
                    ['exception' => $e]
                );

            return null;
        }

        return $attachmentEntityFieldConfig;
    }

    /**
     * @Route("/widget/choose-image", name="oro_digital_asset_widget_choose_image")
     * @Template("@OroDigitalAsset/DigitalAsset/widget/choose.html.twig")
     * @AclAncestor("oro_digital_asset_create")
     *
     * @return array|RedirectResponse
     */
    public function chooseImageAction()
    {
        $mimeTypes = $this->get(FileConstraintsProvider::class)->getImageMimeTypes();
        $maxFileSize = $this->get(FileConstraintsProvider::class)->getMaxSize();

        return $this->handleChooseForm(true, $mimeTypes, $maxFileSize);
    }

    /**
     * @Route("/widget/choose-file", name="oro_digital_asset_widget_choose_file")
     * @Template("@OroDigitalAsset/DigitalAsset/widget/choose.html.twig")
     * @AclAncestor("oro_digital_asset_create")
     *
     * @return array|RedirectResponse
     */
    public function chooseFileAction()
    {
        $mimeTypes = $this->get(FileConstraintsProvider::class)->getFileMimeTypes();
        $maxFileSize = $this->get(FileConstraintsProvider::class)->getMaxSize();

        return $this->handleChooseForm(false, $mimeTypes, $maxFileSize);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                UpdateHandlerFacade::class,
                TranslatorInterface::class,
                LoggerInterface::class,
                EntityClassNameHelper::class,
                EntityConfigManager::class,
                FileConstraintsProvider::class,
            ]
        );
    }
}
