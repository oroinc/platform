<?php

namespace Oro\Bundle\DigitalAssetBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileTitleProviderInterface;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

/**
 * Provides title for file, taking it from the related digital asset.
 */
class FileTitleProvider implements FileTitleProviderInterface
{
    /** @var FileTitleProviderInterface */
    private $innerFileTitleProvider;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(
        FileTitleProviderInterface $innerFileTitleProvider,
        LocalizationHelper $localizationHelper,
        ManagerRegistry $doctrine
    ) {
        $this->innerFileTitleProvider = $innerFileTitleProvider;
        $this->localizationHelper = $localizationHelper;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle(File $file, Localization $localization = null): string
    {
        /** @var DigitalAsset|null $digitalAsset */
        $digitalAsset = $file->getDigitalAsset();

        // Gets digital asset if the given file is a source file of digital asset.
        if (!$digitalAsset && $file->getParentEntityClass() === DigitalAsset::class) {
            $digitalAsset = $this->doctrine->getRepository(DigitalAsset::class)->find($file->getParentEntityId());
        }

        if (!$digitalAsset) {
            return $this->innerFileTitleProvider->getTitle($file, $localization);
        }

        return $this->localizationHelper->getLocalizedValue($digitalAsset->getTitles(), $localization);
    }
}
