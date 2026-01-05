<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Behat\Context;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\FormBundle\Tests\Behat\Context\WysiwygContext;

/**
 * Basic feature context that may be used for digital assets and Wysiwyg
 */
class DigitalAssetWysiwygContext extends WysiwygContext
{
    /**
     * Example: When I fill in WYSIWYG "CMS Page Content" with "tiger.svg" image in "Content"
     * phpcs:disable
     * @When /^(?:|I )fill in WYSIWYG "(?P<wysiwygElementName>[^"]+)" with "(?P<fileName>(?:[^"]|\\")*)" image in "(?P<text>(?:[^"]|\\")*)"$/
     * phpcs:enable
     *
     * @param string $wysiwygElementName
     * @param string $fileName
     * @param string $text
     */
    public function fillWysiwygWithImageContentField($wysiwygElementName, $fileName, $text)
    {
        $file = $this->getDigitalAssetFile($fileName);
        self::assertNotNull($file, sprintf(
            'DigitalAsset "%s" not found in Oro',
            $fileName
        ));

        $text = str_replace($fileName, $file->getParentEntityId(), $text);

        $this->fillWysiwygContentField($wysiwygElementName, $text);
    }

    private function getDigitalAssetFile(string $fileName): ?File
    {
        $qb = $this->getAppContainer()
            ->get('doctrine')
            ->getRepository(File::class)
            ->createQueryBuilder('file');

        return $qb->where(
            $qb->expr()->eq('file.originalFilename', ':name'),
            $qb->expr()->eq('file.parentEntityClass', ':parentEntityClass'),
        )
            ->setParameters([
            ':name' => $fileName,
            ':parentEntityClass' => DigitalAsset::class,
        ])
            ->getQuery()
            ->getOneOrNullResult();
    }
}
