<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Functional\Controller;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicFileControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    private function getTestPublicFileManager(): FileManager
    {
        return self::getContainer()->get('oro_gaufrette.tests.public_file_manager');
    }

    private function getTestNotPublicFileManager(): FileManager
    {
        return self::getContainer()->get('oro_gaufrette.tests.not_public_file_manager');
    }

    public function testGetPublicFileForNotExistingFile()
    {
        $fileName = 'test.txt';
        $fileManager = $this->getTestPublicFileManager();
        $fileManager->deleteFile($fileName);
        $this->client->request(
            'GET',
            $this->getUrl('oro_gaufrette_public_file', [
                'subDirectory' => 'test-public',
                'fileName'     => $fileName
            ])
        );
        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 404);
    }

    public function testGetPublicFileForNotPublicFilesystem()
    {
        $fileName = 'test.txt';
        $fileManager = $this->getTestNotPublicFileManager();
        $fileManager->deleteFile($fileName);
        $fileManager->writeToStorage('test content', $fileName);
        try {
            $this->client->request(
                'GET',
                $this->getUrl('oro_gaufrette_public_file', [
                    'subDirectory' => 'test-not-public',
                    'fileName'     => $fileName
                ])
            );
            $result = $this->client->getResponse();
            self::assertResponseStatusCodeEquals($result, 404);
        } finally {
            $fileManager->deleteFile($fileName);
        }
    }

    public function testGetPublicFileForExistingFile()
    {
        $fileName = 'test.txt';
        $fileContent = 'test content';
        $fileManager = $this->getTestPublicFileManager();
        $fileManager->deleteFile($fileName);
        $fileManager->writeToStorage($fileContent, $fileName);
        try {
            $this->client->request(
                'GET',
                $this->getUrl('oro_gaufrette_public_file', [
                    'subDirectory' => 'test-public',
                    'fileName'     => $fileName
                ])
            );

            $response = $this->client->getResponse();
            self::assertResponseStatusCodeEquals($response, 200);
            self::assertInstanceOf(BinaryFileResponse::class, $response);

            ob_start();
            $response->sendContent();
            $content = ob_get_clean();
            self::assertEquals($fileContent, $content);
        } finally {
            $fileManager->deleteFile($fileName);
        }
    }
}
