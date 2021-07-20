<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AttachmentImageContext extends AttachmentContext implements KernelAwareContext, OroPageObjectAware
{
    use PageObjectDictionary;

    /** @var int[] */
    private $filesCount = [];

    /** @var string[] */
    private $rememberedFilenames = [];

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

        $result = $this->getDriver()->evaluateScript($imageIsLoadedScript);

        $this->assertTrue($result, sprintf('Image %s is not loaded', $imgElementName));
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
            $this->assertEquals(
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
            $this->assertEquals(
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
        $files = $this->getContainer()->get('oro_attachment.file_manager')->findFiles();
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

        $this->assertArrayHasKey($name, $this->rememberedFilenames);
        $this->assertEquals($this->rememberedFilenames[$name], $this->getFileFilename($name));
    }

    /**
     * Example: Then filename of the file "product1" is not as remembered
     *
     * @Then /^filename of the file "(?P<name>(?:[^"]|\\")*)" is not as remembered$/
     */
    public function filenameOfFileIsNotAsRemembered(string $name): void
    {
        $name = $this->fixStepArgument($name);

        $this->assertArrayHasKey($name, $this->rememberedFilenames);
        $this->assertNotEquals($this->rememberedFilenames[$name], $this->getFileFilename($name));
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

        $this->assertEquals(
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

        $this->assertNotEquals(
            $this->rememberedFilenames[$image],
            $filename,
            sprintf(
                'Filename of the image %s was not expected to be equal to the previously remembered one',
                $image
            )
        );
    }

    private function getImageFilename(string $imageName): string
    {
        $image = $this->createElement($imageName);
        $this->assertTrue($image->isValid(), sprintf('Image %s was not found', $imageName));

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
}
