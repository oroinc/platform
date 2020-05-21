<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Functional\Controller;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\EmbeddedFormBundle\Tests\Functional\DataFixtures\LoadEmbeddedFormData;
use Oro\Bundle\EmbeddedFormBundle\Tests\Functional\Stubs\EmbeddedFormStub;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class EmbedFormControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([
            LoadEmbeddedFormData::class,
        ]);
        $this->getContainer()
            ->get('oro_embedded_form.manager')
            ->addFormType(EmbeddedFormStub::class);
    }

    public function testSubmit()
    {
        /** @var EmbeddedForm $feedbackForm */
        $feedbackForm = $this->getReference(LoadEmbeddedFormData::EMBEDDED_FORM);

        $this->client->followRedirects(true);
        $this->client->request(
            'POST',
            $this->getUrl('oro_embedded_form_submit', ['id' => $feedbackForm->getId()]),
            [
                'embedded_form' => [
                    'title' => 'Test title',
                    'css' => 'input { color: red; }',
                    'successMessage' => 'Test success message',
                    'formType' => EmbeddedFormStub::class,
                    '_token' => $this->getCsrfToken('embedded_form')->getValue()
                ],
            ]
        );

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString($feedbackForm->getSuccessMessage(), $result->getContent());
    }

    public function testSubmitPageIsRenderedSuccessfully()
    {
        /** @var EmbeddedForm $feedbackForm */
        $feedbackForm = $this->getReference(LoadEmbeddedFormData::EMBEDDED_FORM);

        $this->client->request(
            'GET',
            $this->getUrl('oro_embedded_form_submit', ['id' => $feedbackForm->getId()])
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCsrfToken($tokenId)
    {
        $container = $this->getContainer();

        $session = $container->get('session');
        $request = new Request([], [], ['_route' => 'oro_embedded_form_submit'], [$session->getName() => 'test']);

        //emulation of embedded form request in order to CsrfTokenStorageDecorator set token to correct storage
        $container->get('request_stack')
            ->push($request);

        return parent::getCsrfToken($tokenId);
    }
}
