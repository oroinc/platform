<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Behat\Context;

use Behat\Mink\Element\NodeElement;
use GuzzleHttp\Client;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Psr\Http\Message\ResponseInterface;

class FeatureContext extends OroFeatureContext
{
    private ?ResponseInterface $pdfFileResponse = null;

    private ?string $pdfFileUrl = null;

    /**
     * @When I download the PDF file from the :linkText link
     */
    public function iDownloadTheFileFromLink(string $linkText): void
    {
        $linkElement = $this->getSession()->getPage()->findLink($linkText);
        if (!$linkElement) {
            self::fail(sprintf('Link with text "%s" not found on the page.', $linkText));
        }

        $pdfFileUrl = $this->getFileUrl($linkElement);

        $client = new Client([
            'verify' => false,
            'cookies' => $this->getCookieJar($this->getSession()),
            'allow_redirects' => true,
            'http_errors' => false,
        ]);

        $this->pdfFileResponse = $client->get($pdfFileUrl);
    }

    private function getFileUrl(NodeElement $linkElement): string
    {
        $href = $linkElement->getAttribute('href');
        if (!$href) {
            self::fail(sprintf('Link "%s" does not have an href attribute.', $linkElement->getText()));
        }

        $absoluteUrl = $this->locatePath($href);
        $validatedUrl = filter_var($absoluteUrl, FILTER_VALIDATE_URL);

        self::assertIsString($validatedUrl, sprintf('URL "%s" is not valid.', $absoluteUrl));

        return $validatedUrl;
    }

    /**
     * @When I remember the PDF file URL from the :linkText link
     */
    public function iRememberTheFileUrlFromLink(string $linkText): void
    {
        $linkElement = $this->getSession()->getPage()->findLink($linkText);
        if (!$linkElement) {
            self::fail(sprintf('Link with text "%s" not found on the page.', $linkText));
        }

        $pdfFileUrl = $this->getFileUrl($linkElement);

        $this->pdfFileUrl = $pdfFileUrl;
    }

    /**
     * @When I follow the remembered PDF file URL
     */
    public function iFollowTheRememberedFileUrl(): void
    {
        if (null === $this->pdfFileUrl) {
            self::fail('No file URL remembered. Please remember a URL first using corresponding step.');
        }

        $this->visitPath($this->pdfFileUrl);
    }

    /**
     * @Then the downloaded PDF file should be a valid PDF
     */
    public function theDownloadedFileShouldBeAValidPdf(): void
    {
        $response = $this->getResponse();
        $contentType = $response->getHeaderLine('Content-Type');

        self::assertMatchesRegularExpression(
            '#application/(pdf|force-download)#i',
            $contentType,
            sprintf('Expected Content-Type "application/pdf" or "application/force-download", got "%s".', $contentType)
        );

        $body = (string)$response->getBody();

        self::assertStringStartsWith('%PDF', $body, 'Downloaded file does not start with "%PDF".');
        self::assertStringContainsString('%%EOF', $body, 'Downloaded file does not contain "%%EOF".');
    }

    /**
     * @Then the downloaded PDF file should contain :text
     */
    public function theDownloadedPdfShouldContain(string $expectedText): void
    {
        $body = (string)$this->getResponse()->getBody();

        self::assertStringContainsString(
            $expectedText,
            $body,
            sprintf('Expected text "%s" not found in the PDF content.', $expectedText)
        );
    }

    /**
     * @Then the PDF response status code should be :expectedStatus
     */
    public function theDownloadedResponseStatusCodeShouldBe(int $expectedStatus): void
    {
        $actualStatus = $this->getResponse()->getStatusCode();

        self::assertSame(
            $expectedStatus,
            $actualStatus,
            sprintf('Expected status code %d but got %d.', $expectedStatus, $actualStatus)
        );
    }

    private function getResponse(): ResponseInterface
    {
        if (null === $this->pdfFileResponse) {
            self::fail('No downloaded PDF file available.');
        }

        return $this->pdfFileResponse;
    }
}
