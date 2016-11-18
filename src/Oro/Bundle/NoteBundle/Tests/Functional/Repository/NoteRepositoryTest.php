<?php

namespace Oro\Bundle\NoteBundle\Tests\Functional\Repository;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\NoteBundle\Entity\Repository\NoteRepository;
use Oro\Bundle\NoteBundle\Tests\Functional\DataFixtures\LoadNoteData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class NoteRepositoryTest extends WebTestCase
{
    /**
     * @var NoteRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadNoteData::class]);

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroNoteBundle:Note');
    }

    /**
     * @dataProvider getAssociatedNotesQueryBuilderDataProvider
     * @param array $data
     * @param array $expected
     */
    public function testGetAssociatedNotesQueryBuilder(array $data, array $expected)
    {
        $actualQB = $this->repository->getAssociatedNotesQueryBuilder(
            $data['class'],
            $this->getReference($data['relatedEntityReference'])->getId(),
            $data['page'],
            $data['limit']
        );
        $actual = $actualQB->getQuery()->execute();

        $expected = $this->resolveReferencesArray($expected);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function getAssociatedNotesQueryBuilderDataProvider()
    {
        return [
            'Query execution should return correct result without page and limit params provided' => [
                'data' => [
                    'class' => Contact::class,
                    'relatedEntityReference' => 'oro_note:contact:alex_smith',
                    'page' => null,
                    'limit' => null
                ],
                'expected' => [
                    'oro_note:note:bar',
                    'oro_note:note:baz',
                ],
            ],
            'Query execution should return correct result with page and limit param provided' => [
                'data' => [
                    'class' => Account::class,
                    'relatedEntityReference' => 'oro_note:account:john_doe',
                    'page' => 2,
                    'limit' => 2
                ],
                'expected' => [
                    'oro_note:note:baz',
                ],
            ]
        ];
    }

    /**
     * @param array $references
     *
     * @return array
     */
    protected function resolveReferencesArray(array $references)
    {
        return array_map(function ($reference) {
            return $this->getReference($reference);
        }, $references);
    }
}
