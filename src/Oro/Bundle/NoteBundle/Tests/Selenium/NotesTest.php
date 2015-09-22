<?php

namespace Oro\Bundle\NoteBundle\Tests\Selenium;

use Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages\ConfigEntities;
use Oro\Bundle\NoteBundle\Tests\Selenium\Pages\Notes;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;

class NotesTest extends Selenium2TestCase
{
    const USERNAME  = 'admin';

    /**
     * Test that user entity do not have Notes functionality On by default
     */
    public function testAddNoteNotAvailable()
    {
        $login = $this->login();
        /** @var Users $login */
        $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open([PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN]);
        /** @var Notes $login */
        $login->openNotes('Oro\Bundle\NoteBundle')
            ->addNoteButtonNotAvailable();
    }

    /**
     * Test Notes functionality set On
     * @depends testAddNoteNotAvailable
     * @return string
     */
    public function testNoteOn()
    {
        $entityName = 'User';

        $login = $this->login();
        /** @var ConfigEntities $login */
        $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            ->filterBy('Name', $entityName, 'is equal to')
            ->filterByMultiselect('Module', ['OroUserBundle'])
            ->open([$entityName])
            ->edit()
            ->enableNotes()
            ->save()
            ->updateSchema()
            ->assertMessage('Schema updated');
    }

    /**
     * Test add new Note to User entity
     * @depends testNoteOn
     * @return string
     */
    public function testAddNote()
    {
        $note = 'Some note_' . mt_rand();

        $login = $this->login();
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open([PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN]);
        /** @var Notes $login */
        $login->openNotes('Oro\Bundle\NoteBundle')
            ->addNote()
            ->setNoteMessage($note)
            ->saveNote()
            ->assertMessage('Note saved')
            ->checkNote($note);

        return $note;
    }

    /**
     * Test editing of existing Note
     * @depends testAddNote
     * @param $note
     * @return string
     */
    public function testEditNote($note)
    {
        $newNote = 'Update_'.$note;

        $login = $this->login();
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open([PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN]);
        /** @var Notes $login */
        $login->openNotes('Oro\Bundle\NoteBundle')
            ->noteAction($note, 'Update Note')
            ->setNoteMessage($newNote)
            ->saveNote()
            ->assertMessage('Note saved')
            ->checkNote($newNote);

        return $newNote;
    }

    /**
     * Test deletion of existing Note
     * @depends testEditNote
     * @param $note
     */
    public function testDeleteNote($note)
    {
        $login = $this->login();
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open([PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN]);
        /** @var Notes $login */
        $login->openNotes('Oro\Bundle\NoteBundle')
            ->noteAction($note, 'Delete Note')
            ->assertMessage('Activity item deleted');
    }

    /**
     * Test turn Off Notes functionality at user entity
     * @depends testAddNote
     */
    public function testAddNoteOff()
    {
        $entityName = 'User';

        $login = $this->login();
        /** @var ConfigEntities $login */
        $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            ->filterBy('Name', $entityName, 'is equal to')
            ->open([$entityName])
            ->edit()
            ->enableNotes('No')
            ->save()
            ->assertMessage('Entity saved');
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open([PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN]);
        /** @var Notes $login */
        $login->openNotes('Oro\Bundle\NoteBundle')
            ->addNoteButtonNotAvailable();
    }

    public function testCloseWidgetWindow()
    {
        $login = $this->login();
        $login->closeWidgetWindow();
    }
}
