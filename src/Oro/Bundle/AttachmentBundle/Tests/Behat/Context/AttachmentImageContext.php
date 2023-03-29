<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AttachmentImageContext extends AttachmentContext implements OroPageObjectAware
{
    use PageObjectDictionary;

    /** @var int[] */
    private $filesCount = [];

    /** @var string[] */
    private $rememberedFilenames = [];

    /** @var array[] [file name => [[path, ...], media cache manager], ...] */
    private array $rememberedImagePaths = [];

    public function getResizeAttachmentUrl($entity, string $attachmentField): string
    {
        $attachment = $this->getAttachmentByEntity($entity, $attachmentField);

        return $this->getAttachmentManager()->getResizedImageUrl($attachment);
    }

    public function getFilteredAttachmentUrl($entity, string $attachmentField): string
    {
        $attachment = $this->getAttachmentByEntity($entity, $attachmentField);

        return $this->getAttachmentManager()->getFilteredImageUrl($attachment, 'avatar_xsmall');
    }

    protected function assertResponseSuccess(ResponseInterface $response): void
    {
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($this->getAttachmentManager()->isImageType($response->getHeader('Content-Type')[0]));
    }

    protected function assertResponseFail(ResponseInterface $response): void
    {
        self::assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_FORBIDDEN]);
        static::assertStringContainsString('text/html', $response->getHeader('Content-Type')[0]);
    }

    /**
     * @Then /^(?:|I) download "(?P<attachmentName>[^"]+)" attachment/
     */
    public function downloadFile(string $attachmentName): void
    {
        $attachmentLink = $this->getSession()->getPage()->findLink($attachmentName);
        $url = $attachmentLink->getAttribute('href');
        $response = $this->downloadAttachment($url);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        static::assertStringContainsString('application/force-download', $response->getHeader('Content-Type')[0]);
    }

    /**
     * @Then /^(?:|I) can not download "(?P<attachmentName>[^"]+)" attachment/
     */
    public function downloadFileForbidden(string $attachmentName): void
    {
        $attachmentLink = $this->getSession()->getPage()->findLink($attachmentName);
        $url = $attachmentLink->getAttribute('href');
        $response = $this->downloadAttachment($url);
        $this->assertResponseFail($response);
    }

    /**
     * Checks if image is successfully loaded and displayed on page
     *
     * @Then /^image "(?P<attachmentName>[^"]+)" is loaded$/
     * Example: Then "Product 1 Grid Image" is loaded
     */
    public function assertImageIsLoaded(string $imgElementName): void
    {
        $result = $this->spin(function () use ($imgElementName) {
            $imgElement = $this->elementFactory->createElement($imgElementName);
            $imgElementXpath = $imgElement->getXpath();
            $imageIsLoadedScript = <<<JS
                (function () {
                    var image = document.evaluate(
                        '$imgElementXpath',
                        document,
                        null,
                        XPathResult.FIRST_ORDERED_NODE_TYPE,
                        null
                        ).singleNodeValue;
                    
                    if (!image) {
                        return false;
                    }
                    
                    return image.complete && typeof image.naturalWidth !== "undefined" && image.naturalWidth !== 0;
                })();
JS;

            return $this->getDriver()->evaluateScript($imageIsLoadedScript);
        }, 5);

        self::assertTrue($result, sprintf('Image %s is not loaded', $imgElementName));
    }

    /**
     * @Given /^(?:|I )remember number of files in attachment directory$/
     * @Given /^(?:|I )remember number of files with extension "(?P<extension>[^"]+)" in attachment directory$/
     */
    public function rememberNumberOfAttachmentFiles(string $extension = ''): void
    {
        $this->filesCount[$extension] = $this->countFilesInAttachmentFilesystem($extension);
    }

    //@codingStandardsIgnoreStart
    /**
     * @Then /^number of files in attachment directory is (?P<count>[\d]+) (?P<operator>(?:less|more)) than remembered$/
     * @Then /^number of files with extension "(?P<extension>[^"]+)" in attachment directory is (?P<count>[\d]+) (?P<operator>(?:less|more)) than remembered$/
     */
    //@codingStandardsIgnoreEnd
    public function numberOfAttachmentFilesIsChangedBy(string $operator, int $count, string $extension = ''): void
    {
        $currentCount = $this->countFilesInAttachmentFilesystem($extension);
        $rememberedCount = $this->filesCount[$extension] ?? 0;

        if ($operator === 'less') {
            self::assertEquals(
                $rememberedCount - $currentCount,
                $count,
                sprintf(
                    'Current number of files %d is not less by %d than remembered %d',
                    $currentCount,
                    $count,
                    $rememberedCount
                )
            );
        }

        if ($operator === 'more') {
            self::assertEquals(
                $count,
                $currentCount - $rememberedCount,
                sprintf(
                    'Current number of files %d is not more by %d than remembered %d',
                    $currentCount,
                    $count,
                    $rememberedCount
                )
            );
        }
    }

    private function countFilesInAttachmentFilesystem(string $extension = ''): int
    {
        $files = $this->getAppContainer()->get('oro_attachment.file_manager')->findFiles();
        if ($extension) {
            $resultFiles = [];
            $pattern = sprintf('*.%s', ltrim($extension, '.'));
            foreach ($files as $file) {
                if (fnmatch($pattern, $file)) {
                    $resultFiles[] = $file;
                }
            }
            $files = $resultFiles;
        }

        return count($files);
    }

    /**
     * Example: I remember filename of the file "product1"
     *
     * @Given /^I remember filename of the file "(?P<name>(?:[^"]|\\")*)"$/
     */
    public function iRememberFilenameOfFile(string $name): void
    {
        $name = $this->fixStepArgument($name);

        $this->rememberedFilenames[$name] = $this->getFileFilename($name);
    }

    /**
     * Example: Then filename of the file "product1" is as remembered
     *
     * @Then /^filename of the file "(?P<name>(?:[^"]|\\")*)" is as remembered$/
     */
    public function filenameOfFileIsAsRemembered(string $name): void
    {
        $name = $this->fixStepArgument($name);

        self::assertArrayHasKey($name, $this->rememberedFilenames);
        self::assertEquals($this->rememberedFilenames[$name], $this->getFileFilename($name));
    }

    /**
     * Example: Then filename of the file "product1" is not as remembered
     *
     * @Then /^filename of the file "(?P<name>(?:[^"]|\\")*)" is not as remembered$/
     */
    public function filenameOfFileIsNotAsRemembered(string $name): void
    {
        $name = $this->fixStepArgument($name);

        self::assertArrayHasKey($name, $this->rememberedFilenames);
        self::assertNotEquals($this->rememberedFilenames[$name], $this->getFileFilename($name));
    }

    /**
     * Example: I remember filename of the image "cat1.jpg"
     *
     * @Then /^I remember filename of the image "(?P<image>(?:[^"]|\\")*)"$/
     */
    public function iRememberFilenameOfImage(string $image): void
    {
        $image = $this->fixStepArgument($image);

        $this->rememberedFilenames[$image] = $this->getImageFilename($image);
    }

    /**
     * Example: filename of the image "cat1.jpg" is as remembered
     *
     * @Then /^filename of the image "(?P<image>(?:[^"]|\\")*)" is as remembered$/
     */
    public function filenameOfImageIsAsRemembered(string $image): void
    {
        $image = $this->fixStepArgument($image);
        $filename = $this->getImageFilename($image);

        self::assertEquals(
            $this->rememberedFilenames[$image],
            $filename,
            sprintf(
                'Filename of the image %s was expected to be equal to the previously remembered one',
                $image
            )
        );
    }

    /**
     * Example: filename of the image "cat1.jpg" is not as remembered
     *
     * @Then /^filename of the image "(?P<image>(?:[^"]|\\")*)" is not as remembered$/
     */
    public function filenameOfImageIsNotAsRemembered(string $image): void
    {
        $image = $this->fixStepArgument($image);
        $filename = $this->getImageFilename($image);

        self::assertNotEquals(
            $this->rememberedFilenames[$image],
            $filename,
            sprintf(
                'Filename of the image %s was not expected to be equal to the previously remembered one',
                $image
            )
        );
    }

    /**
     * Example: images "cat1.jpg" and "cat2.jpg" have different filenames
     *
     * @Then /^images "(?P<firstImage>(?:[^"]|\\")*)" and "(?P<secondImage>(?:[^"]|\\")*)" have different filenames$/
     */
    public function imagesHaveDifferentFileNames(string $firstImage, string $secondImage): void
    {
        $firstImage = $this->fixStepArgument($firstImage);
        $firstFilename = $this->getImageFilename($firstImage);

        $secondImage = $this->fixStepArgument($secondImage);
        $secondFilename = $this->getImageFilename($secondImage);

        self::assertNotEquals(
            $firstFilename,
            $secondFilename,
            sprintf(
                'Filenames of the images %s and %s was not expected to be equal',
                $firstImage,
                $secondImage
            )
        );
    }

    private function getImageFilename(string $imageName): string
    {
        $image = $this->createElement($imageName);
        self::assertTrue($image->isValid(), sprintf('Image %s was not found', $imageName));

        preg_match(
            '/\/media\/cache\/attachment\/.+\/(?P<filename>.+)$/',
            $image->getAttribute('src'),
            $matches
        );
        self::assertNotEmpty($matches['filename'], sprintf('Filename not found for image %s', $imageName));

        return $matches['filename'];
    }

    private function getFileFilename(string $name): string
    {
        if ($this->hasElement($name)) {
            $link = $this->createElement($name);
        } else {
            $link = $this->getSession()
                ->getPage()
                ->find(
                    'xpath',
                    sprintf('//a[contains(@data-filename, "%s")]', $name)
                );

            self::assertNotNull($link, sprintf('File with name "%s" was not found', $name));
        }

        preg_match('/.+\/(?P<filename>.+?)$/', $link->getAttribute('href'), $matches);
        self::assertNotEmpty($matches['filename'], sprintf('Filename not found for file %s', $name));

        return $matches['filename'];
    }

    /**
     * Example: I should see picture "Attachment Picture" element
     *
     * @Then /^I should see picture "(?P<elementName>[^"]+)" element$/
     *
     * @param NodeElement|string $picture
     */
    public function iShouldSeePictureElement(NodeElement|string $picture): void
    {
        $webpConfiguration = $this->getAppContainer()->get('oro_attachment.tools.webp_configuration');

        if (!$webpConfiguration->isDisabled()) {
            $this->iShouldSeeWebpImageInElement($picture);
        } else {
            $this->findPictureTag($picture);
        }
    }

    private function iShouldSeeWebpImageInElement(NodeElement|string $picture): void
    {
        $picture = $this->findPictureTag($picture);

        $source = $picture->find('xpath', '//source[@type="image/webp"][last()]');
        if ($source) {
            $imageUrl = $source->getAttribute('srcset');
        } else {
            $img = $picture->find('xpath', '//img');
            $imageUrl = $img->getAttribute('src');
        }

        self::assertNotEmpty($imageUrl, sprintf('Image url was not found in "%s"', $picture->getXpath()));

        $imageUrl = preg_replace('/\\?.*/', '', $imageUrl);

        self::assertStringEndsWith(
            '.webp',
            $imageUrl,
            sprintf('Expected image url with ".webp" extension, got "%s"', $imageUrl)
        );

        $response = $this->loadImage($imageUrl, true);

        self::assertEquals(
            200,
            $response->getStatusCode(),
            sprintf(
                'Expected "200" status code, got "%s" when requested the image url "%s"',
                $response->getStatusCode(),
                $imageUrl
            )
        );
    }

    private function findPictureTag(NodeElement|string $picture): NodeElement
    {
        if (is_string($picture)) {
            $pictureName = $picture;
            $picture = $this->createElement($pictureName);

            self::assertNotNull($picture, sprintf('Element with name "%s" not found', $pictureName));
        }

        self::assertTrue($picture->isVisible(), 'Picture is not visible');

        return $picture;
    }

    private function getImageFiles(string $imageName): array
    {
        $fileRepository = $this->getAppContainer()
            ->get('doctrine')
            ->getManagerForClass(File::class)->getRepository(File::class);

        /** @var File[] $files */
        $files = $fileRepository->findBy(
            ['originalFilename' => $imageName],
            ['id' => 'DESC']
        );
        self::assertNotEmpty($files, sprintf('File for image %s not found', $imageName));

        return $files;
    }

    /**
     * @Then /^(?:|I )expect image files created and remember paths:$/
     * Example: Then expect image files created and remember paths:
     *      | image.jpg |
     *      | name.png  |
     */
    public function assertImageFilesCreated(TableNode $table): void
    {
        $this->assertImageFilesCreatedAndRemembered($table);
    }

    /**
     * @Then /^(?:|I )expect paths don't exist for images:$/
     * Example: Then expect paths don't exist for images:
     *      | image.jpg |
     *      | name.png  |
     */
    public function assertPathsDontExist(TableNode $table): void
    {
        $imagePaths = $this->rememberedImagePaths;
        $this->rememberedImagePaths = [];

        $rows = $table->getRows();
        foreach ($rows as $row) {
            $imageName = $row[0];

            self::assertArrayHasKey(
                $imageName,
                $imagePaths,
                sprintf('File paths was not remembered for image %s', $imageName)
            );

            /** @var GaufretteFileManager $mediaCacheManager */
            [$paths, $mediaCacheManager] = $imagePaths[$imageName];
            foreach ($paths as $path) {
                self::assertFalse(
                    $mediaCacheManager->hasFile($path),
                    sprintf('Failed assert that file %s does not exist', $path)
                );
            }
        }
    }

    /**
     * @Then /^(?:|I )expect public image files created:$/
     * Example: Then expect public image files created:
     *      | image.jpg |
     *      | name.png  |
     */
    public function assertPublicImageFilesCreated(TableNode $table): void
    {
        /** @var GaufretteFileManager $mediaCacheManager */
        $mediaCacheManager = $this->getAppContainer()->get('oro_attachment.manager.public_mediacache');

        $this->assertImageFilesCreatedAndRemembered($table, $mediaCacheManager);
    }

    public function assertImageFilesCreatedAndRemembered(
        TableNode $table,
        GaufretteFileManager $mediaCacheManager = null
    ): void {
        $this->rememberedImagePaths = [];

        $rows = $table->getRows();
        foreach ($rows as $row) {
            $imageName = $row[0];

            $existingPaths = [];
            $files = $this->getImageFiles($imageName);
            foreach ($files as $file) {
                $paths = $this->getAppContainer()
                    ->get('oro_attachment.provider.image_file_names')
                    ->getFileNames($file);
                self::assertNotEmpty($paths, sprintf('File paths for image %s not found', $imageName));

                $mediaCacheManager = $mediaCacheManager ?? $this->getAppContainer()
                        ->get('oro_attachment.media_cache_manager_registry')
                        ->getManagerForFile($file);

                foreach ($paths as $path) {
                    if ($mediaCacheManager->hasFile($path)) {
                        $existingPaths[] = $path;
                    }
                }
            }

            self::assertNotEmpty(
                $existingPaths,
                sprintf('At least one image file should be created for image %s', $imageName)
            );

            $this->rememberedImagePaths[$imageName] = [$existingPaths, $mediaCacheManager];
        }
    }
}
