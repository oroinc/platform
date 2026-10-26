<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

/**
 * Loads files with a stored MIME type and original file name that do not have to match the file content.
 */
class LoadFileMimeTypeData extends AbstractFixture
{
    public const string FILE_HTML = 'file_mime_type_html';
    public const string FILE_TEXT_UPPERCASE_WITH_CHARSET = 'file_mime_type_text_uppercase_with_charset';
    public const string FILE_SVG = 'file_mime_type_svg';
    public const string FILE_FILENAME_WITH_CONTROL_CHARS = 'file_mime_type_filename_with_control_chars';

    private const array FILES = [
        self::FILE_HTML => ['text/html', null],
        self::FILE_TEXT_UPPERCASE_WITH_CHARSET => ['TEXT/PLAIN; charset=windows-1251', null],
        self::FILE_SVG => ['image/svg+xml', null],
        self::FILE_FILENAME_WITH_CONTROL_CHARS => ['text/html', "probe\r\n.html"],
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach (self::FILES as $reference => [, $originalFilename]) {
            $file = new File();
            $file->setFile(new ComponentFile(__DIR__ . '/files/file_1.txt'));
            $file->setOriginalFilename($originalFilename ?? $reference . '.txt');
            $file->setParentEntityClass(\stdClass::class);
            $file->setParentEntityId(1);
            $file->setParentEntityFieldName('fieldA');
            $manager->persist($file);
            $this->setReference($reference, $file);
        }
        // The upload derives the type from the content, so the files must be stored first.
        $manager->flush();

        // Clear the file so the flush keeps the type below instead of deriving it from the content again.
        foreach (self::FILES as $reference => [$mimeType]) {
            /** @var File $file */
            $file = $this->getReference($reference);
            $file->setFile(null);
            $file->setMimeType($mimeType);
        }
        $manager->flush();
    }
}
