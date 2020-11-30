<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AttachmentImageContext extends AttachmentContext implements KernelAwareContext, OroPageObjectAware
{
    use PageObjectDictionary;

    /** @var int[] */
    private $filesCount;

    /** @var string[] */
    private $rememberedFilenames;

    /**
     * @param $entity
     * @param string $attachmentField
     *
     * @return string
     */
    public function getResizeAttachmentUrl($entity, string $attachmentField): string
    {
        $attachment = $this->getAttachmentByEntity($entity, $attachmentField);

        return $this->getAttachmentManager()->getResizedImageUrl($attachment);
    }

    /**
     * @param $entity
     * @param string $attachmentField
     *
     * @return string
     */
    public function getFilteredAttachmentUrl($entity, string $attachmentField): string
    {
        $attachment = $this->getAttachmentByEntity($entity, $attachmentField);

        return $this->getAttachmentManager()->getFilteredImageUrl($attachment, 'avatar_xsmall');
    }

    /**
     * @param ResponseInterface $response
     */
    protected function assertResponseSuccess(ResponseInterface $response): void
    {
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($this->getAttachmentManager()->isImageType($response->getHeader('Content-Type')[0]));
    }

    /**
     * @param ResponseInterface $response
     */
    protected function assertResponseFail(ResponseInterface $response): void
    {
        self::assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_FORBIDDEN]);
        self::assertContains('text/html', $response->getHeader('Content-Type')[0]);
    }

    /**
     * @Then /^(?:|I) download "(?P<attachmentName>[^"]+)" attachment/
     *
     * @param string $attachmentName
     */
    public function downloadFile(string $attachmentName): void
    {
        $attachmentLink = $this->getSession()->getPage()->findLink($attachmentName);
        $url = $attachmentLink->getAttribute('href');
        $response = $this->downloadAttachment($url);
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertContains('application/force-download', $response->getHeader('Content-Type')[0]);
    }

    /**
     * @Then /^(?:|I) can not download "(?P<attachmentName>[^"]+)" attachment/
     *
     * @param string $attachmentName
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
     *
     * @param string $imgElementName
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
     *
     * @param string $extension
     */
    public function rememberNumberOfAttachmentFiles(string $extension = ''): void
    {
        $this->filesCount[$extension] = $this->countFilesInAttachmentDir($extension);
    }

    //@codingStandardsIgnoreStart
    /**
     * @Then /^number of files in attachment directory is (?P<count>[\d]+) (?P<operator>(?:less|more)) than remembered$/
     * @Then /^number of files with extension "(?P<extension>[^"]+)" in attachment directory is (?P<count>[\d]+) (?P<operator>(?:less|more)) than remembered$/
     *
     * @param string $operator
     * @param int $count
     * @param string $extension
     */
    //@codingStandardsIgnoreEnd
    public function numberOfAttachmentFilesIsChangedBy(string $operator, int $count, string $extension = ''): void
    {
        $currentCount = $this->countFilesInAttachmentDir($extension);
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

    /**
     * @param string $extension
     *
     * @return int
     */
    private function countFilesInAttachmentDir(string $extension = ''): int
    {
        $projectDir = $this->getContainer()->getParameter('kernel.project_dir');
        $attachmentDir = $this->getContainer()->getParameter('oro_attachment.filesystem_dir.attachments');
        $filesIterator = Finder::create()
            ->in($projectDir . '/var/' . $attachmentDir)
            ->files();

        if ($extension) {
            $filesIterator->name(sprintf('*.%s', ltrim($extension, '.')));
        }

        return iterator_count($filesIterator);
    }

    /**
     * Example: I remember filename of the file "product1"
     *
     * @Given /^I remember filename of the file "(?P<name>[^"]*)"$/
     * @param string $name
     */
    public function iRememberFilenameOfFile(string $name): void
    {
        $link = $this->getSession()
            ->getPage()
            ->find(
                'xpath',
                sprintf('//a[contains(@data-filename, "%s")]', $name)
            );

        self::assertNotNull($link, sprintf('File with name "%s" have not been found', $name));

        preg_match('/.+\/(?P<filename>.+?)$/', $link->getAttribute('href'), $matches);

        $this->rememberedFilenames[$name] = $matches['filename'];
    }

    /**
     * Example: Then filename of the file "product1" is not as remembered
     *
     * @Then /^filename of the file "(?P<name>[^"]*)" is not as remembered$/
     * @param string $name
     */
    public function filenameOfFileIsNotAsRemembered(string $name): void
    {
        $link = $this->getSession()
            ->getPage()
            ->find(
                'xpath',
                sprintf('//a[contains(@data-filename, "%s")]', $name)
            );

        self::assertNotNull($link, sprintf('File with name "%s" have not been found', $name));

        preg_match('/.+\/(?P<filename>.+?)$/', $link->getAttribute('href'), $matches);

        $this->assertArrayHasKey($name, $this->rememberedFilenames);
        $this->assertNotEquals($this->rememberedFilenames[$name], $matches['filename']);
    }

    /**
     * Example: I remember filename of the image "cat1.jpg"
     *
     * @Then /^I remember filename of the image "(?P<image>(?:[^"]|\\")*)"$/
     * @param string $image
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
     * @param string $image
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
     * @param string $image
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

    /**
     * @param string $imageName
     * @return string
     */
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
}
