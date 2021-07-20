<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Controller;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class SegmentControllerTest extends WebTestCase
{
    private const TEST_SEGMENT = 'Test user segment';
    private const UPDATED_TEST_SEGMENT = 'Updated test user segment';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testCreate(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_segment_create'));

        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['oro_segment_form']['name'] = self::TEST_SEGMENT;
        $formValues['oro_segment_form']['description'] = self::TEST_SEGMENT . ' description';
        $formValues['oro_segment_form']['entity'] = User::class;
        $formValues['oro_segment_form']['type'] = SegmentType::TYPE_STATIC;
        $formValues['oro_segment_form']['recordsLimit'] = 100;
        $formValues['oro_segment_form']['definition'] = QueryDefinitionUtil::encodeDefinition([
            'columns' => [
                [
                    'name' => 'id',
                    'label' => 'Id',
                    'sorting' => 'DESC',
                    'func' => null
                ],
                [
                    'name' => 'firstName',
                    'label' => 'First name',
                    'sorting' => '',
                    'func' => null
                ]
            ],
            'filters' => [
                [
                    'columnName' => 'firstName',
                    'criterion' => [
                        'filter' => 'string',
                        'data' => [
                            'value' => 'test',
                            'type' => '1'
                        ]
                    ]
                ]
            ]
        ]);

        $this->client->followRedirects();
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('Segment saved', $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_segment_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('oro_segments-grid', $crawler->html());
        static::assertStringContainsString(self::TEST_SEGMENT, $result->getContent());
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(): int
    {
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(Segment::class);

        $repository = $em->getRepository(Segment::class);

        /** @var Segment $segment */
        $segment = $repository->findOneBy(['name' => self::TEST_SEGMENT]);

        $crawler = $this->client->request('GET', $this->getUrl('oro_segment_update', ['id' => $segment->getId()]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_segment_form[name]'] = self::UPDATED_TEST_SEGMENT;

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('Segment saved', $crawler->html());

        $em->clear();

        $segment = $repository->find($segment->getId());

        $this->assertNotNull($segment);
        $this->assertEquals(self::UPDATED_TEST_SEGMENT, $segment->getName());

        return $segment->getId();
    }

    /**
     * @depends testUpdate
     */
    public function testClone(int $id): string
    {
        $name = sprintf('Copy of %s', self::UPDATED_TEST_SEGMENT);

        $crawler = $this->client->request('GET', $this->getUrl('oro_segment_clone', ['id' => $id]));

        $form = $crawler->selectButton('Save and Close')->form();
        $this->assertEquals($name, $form['oro_segment_form[name]']->getValue());

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('Segment saved', $crawler->html());

        $repository = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(Segment::class)
            ->getRepository(Segment::class);

        /** @var Segment $originalSegment */
        $originalSegment = $repository->find($id);
        $this->assertNotNull($originalSegment);

        $copiedSegment = $repository->findOneBy(['name' => $name]);
        $this->assertNotNull($copiedSegment);
        $this->assertEquals($name, $copiedSegment->getName());

        $this->assertNotEquals($originalSegment->getId(), $copiedSegment->getId());
        $this->assertNotEquals($originalSegment->getName(), $copiedSegment->getName());
        $this->assertSame($originalSegment->getDescription(), $copiedSegment->getDescription());
        $this->assertSame($originalSegment->getType(), $copiedSegment->getType());
        $this->assertSame($originalSegment->getEntity(), $copiedSegment->getEntity());
        $this->assertSame($originalSegment->getOwner(), $copiedSegment->getOwner());
        $this->assertSame($originalSegment->getDefinition(), $copiedSegment->getDefinition());
        $this->assertSame($originalSegment->getOrganization(), $copiedSegment->getOrganization());
        $this->assertSame($originalSegment->getRecordsLimit(), $copiedSegment->getRecordsLimit());

        return $name;
    }

    /**
     * @depends testClone
     */
    public function testIndexAfterClone(string $name): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_segment_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('oro_segments-grid', $crawler->html());
        static::assertStringContainsString($name, $result->getContent());
    }
}
