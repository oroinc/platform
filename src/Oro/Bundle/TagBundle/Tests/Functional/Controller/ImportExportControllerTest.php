<?php

namespace Oro\Bundle\TagBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class ImportExportControllerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testExportedTemplateContainsTagWithDefaultValue()
    {
        $this->client->followRedirects();
        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_export_template', ['processorAlias' => 'oro_user'])
        );
        /* @var $response BinaryFileResponse */
        $response = $this->getClientInstance()->getResponse();
        $this->assertResponseStatusCodeEquals($response, 200);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\BinaryFileResponse', $response);
        
        $handle = fopen($response->getFile()->getPathname(), 'r');
        $lines = [];
        while (($row = fgetcsv($handle)) !== false) {
            $lines[] = $row;
        }
        $this->assertCount(2, $lines);
        $result = array_combine($lines[0], $lines[1]);
        $this->assertArrayHasKey('Tags', $result);
        $this->assertEquals($result['Tags'], 'custom tag, second tag');
    }

    public function testValidateExportTemplate()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_import_form',
                array(
                    'entity'           => 'Oro\Bundle\UserBundle\Entity\User',
                    '_widgetContainer' => 'dialog'
                )
            )
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);

        $file = $this->getImportTemplate();
        $this->assertTrue(file_exists($file));

        /* @var $form Form */
        $form = $crawler->selectButton('Submit')->form();
        $form->getFormNode()->setAttribute(
            'action',
            $form->getFormNode()->getAttribute('action') . '&_widgetContainer=dialog'
        );
        $form['oro_importexport_import[file]']->upload($file);
        $form['oro_importexport_import[processorAlias]'] = 'oro_user.add_or_replace';
        $this->client->followRedirects(true);
        $this->client->submit($form);
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
    }

    public function testImportEntityWithTag()
    {
        $this->assertNull(
            $this->getTaggingRepository()->findOneByEntityName('Oro\Bundle\UserBundle\Entity\User')
        );

        $this->client->followRedirects(false);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_import_process',
                array(
                    'processorAlias' => 'oro_user.add_or_replace',
                    '_format'        => 'json'
                )
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals(
            array(
                'success'   => true,
                'message'   => 'File was successfully imported.',
                'errorsUrl' => null,
                'importInfo' => sprintf('%s entities were added, %s entities were updated', 0, 1)
            ),
            $data
        );

        $this->assertNotNull(
            $this->getTaggingRepository()->findOneByEntityName('Oro\Bundle\UserBundle\Entity\User')
        );
    }

    /**
     * @return string
     */
    protected function getImportTemplate()
    {
        $result = $this
            ->getContainer()
            ->get('oro_importexport.handler.export')
            ->getExportResult(
                JobExecutor::JOB_EXPORT_TEMPLATE_TO_CSV,
                'oro_user',
                ProcessorRegistry::TYPE_EXPORT_TEMPLATE
            );

        $chains = explode('/', $result['url']);
        return $this
            ->getContainer()
            ->get('oro_importexport.file.file_system_operator')
            ->getTemporaryFile(end($chains))
            ->getRealPath();
    }

    /**
     * @return EntityRepository
     */
    protected function getTaggingRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroTagBundle:Tagging');
    }
}
