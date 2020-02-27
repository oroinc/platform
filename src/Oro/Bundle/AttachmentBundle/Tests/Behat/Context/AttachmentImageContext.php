<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Doctrine\Common\Persistence\ManagerRegistry;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\Repository\FileRepository;
use Oro\Bundle\AttachmentBundle\Manager\ImageRemovalManagerInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class AttachmentImageContext extends AttachmentContext implements KernelAwareContext
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var WebsiteProviderInterface */
    private $websiteProvider;

    /** @var WebsiteManager */
    private $websiteManager;

    /** @var ConfigManager */
    private $configManager;

    /** @var FilterConfiguration */
    private $filterConfiguration;

    /** @var ImageRemovalManagerInterface */
    private $imageRemovalManager;

    /** @var array[] */
    private $imagePaths;

    /**
     * @param WebsiteProviderInterface $websiteProvider
     * @param WebsiteManager $websiteManager
     * @param ConfigManager $configManager
     * @param FilterConfiguration $filterConfiguration
     * @param ImageRemovalManagerInterface $imageRemovalManager
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        WebsiteProviderInterface $websiteProvider,
        WebsiteManager $websiteManager,
        ConfigManager $configManager,
        FilterConfiguration $filterConfiguration,
        ImageRemovalManagerInterface $imageRemovalManager,
        ManagerRegistry $doctrine
    ) {
        $this->doctrine = $doctrine;
        $this->websiteProvider = $websiteProvider;
        $this->websiteManager = $websiteManager;
        $this->configManager = $configManager;
        $this->filterConfiguration = $filterConfiguration;
        $this->imageRemovalManager = $imageRemovalManager;
    }

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
     * @Then /^(?:|I )expect image files created and remember paths:$/
     * Example: Then expect image files created and remember paths:
     *      | image.jpg |
     *      | name.png  |
     *
     * @param TableNode $table
     */
    public function assertImageFilesCreated(TableNode $table)
    {
        self::assertTrue(
            \method_exists($this->imageRemovalManager, 'getFilePaths'),
            sprintf(
                'Provided image removal manager doesn\'t support getting file paths. ' .
                'Manager should provide "getFilePaths" method. Manager class name: %s',
                \get_class($this->imageRemovalManager)
            )
        );

        $this->imagePaths = [];

        $rows = $table->getRows();

        $websites = $this->websiteProvider->getWebsites();
        $dimensions = array_keys($this->filterConfiguration->all());

        foreach ($rows as $row) {
            $paths = [];
            $existingPaths = [];

            $imageName = $row[0];
            $file = $this->getImageFile($imageName);

            /** @var Website $website */
            foreach ($websites as $website) {
                $this->websiteManager->setCurrentWebsite($website);
                $this->configManager->setScopeId($website->getId());

                foreach ($dimensions as $dimension) {
                    $paths[] = $this->imageRemovalManager->getFilePaths($file, $dimension);
                }
            }

            self::assertNotEmpty($paths, sprintf(
                'File paths for image %s not found',
                $imageName
            ));

            foreach ($paths as $innerPaths) {
                if (!is_array($innerPaths)) {
                    $innerPaths = [$innerPaths];
                }

                foreach ($innerPaths as $path) {
                    $mediaCacheDir = 'public/' . $this->getContainer()->getParameter('oro_attachment.media_cache_dir');
                    $path = $mediaCacheDir . '/' . ltrim($path, '/');
                    if (\file_exists($path)) {
                        $existingPaths[md5($path)] = $path;
                    }
                }
            }

            $this->imagePaths[$imageName] = $existingPaths;
        }
    }

    /**
     * @param string $imageName
     * @return File
     */
    private function getImageFile(string $imageName): File
    {
        /** @var FileRepository $fileRepository */
        $fileRepository = $this->doctrine->getManager()->getRepository(File::class);

        /** @var File $file */
        $file = $fileRepository->findOneBy(
            ['originalFilename' => $imageName],
            ['id' => 'DESC']
        );
        self::assertNotNull($file, sprintf(
            'File for image %s not found',
            $imageName
        ));

        return $file;
    }

    /**
     * @Then /^(?:|I )expect paths don't exist for images:$/
     * Example: Then expect paths don't exist for images:
     *      | /var/www/image.jpg  |
     *      | public/cache/images |
     *
     * @param TableNode $table
     */
    public function assertPathsDontExist(TableNode $table)
    {
        $rows = $table->getRows();


        foreach ($rows as $row) {
            $imageName = $row[0];

            self::assertArrayHasKey($imageName, $this->imagePaths, sprintf(
                'File paths not stored for image %s',
                $imageName
            ));

            $paths = $this->imagePaths[$imageName];

            foreach ($paths as $path) {
                $this->assertFileNotExists($path);
            }
        }
    }
}
