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
            ->open(array(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN));
        /** @var Notes $login */
        $login->openNotes('Oro\Bundle\NoteBundle')
            ->addNoteButtonNotAvailable();
    }

    /**
     * Test Notes functionality set On and add new Note to User entity
     * @depends testAddNoteNotAvailable
     * @return string
     */
    public function testAddNoteOn()
    {
        $note = 'Some note_' . mt_rand();
        $entityName = 'User';

        $login = $this->login();
        /** @var ConfigEntities $login */
        $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            ->filterBy('Name', $entityName)
            ->open(array($entityName))
            ->edit()
            ->enableNotes()
            ->save()
            ->updateSchema()
            ->assertMessage('Schema updated');
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open(array(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN));
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
     * @depends testAddNoteOn
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
            ->open(array(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN));
        /** @var Notes $login */
        $login->openNotes('Oro\Bundle\NoteBundle')
            ->editNote($note)
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
            ->open(array(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN));
        /** @var Notes $login */
        $login->openNotes('Oro\Bundle\NoteBundle')
            ->deleteNote($note)
            ->assertMessage('Note deleted');
    }

    /**
     * Test turn Off Notes functionality at user entity
     * @depends testAddNoteOn
     */
    public function testAddNoteOff()
    {
        $entityName = 'User';

        $login = $this->login();
        /** @var ConfigEntities $login */
        $login->openConfigEntities('Oro\Bundle\EntityConfigBundle')
            ->filterBy('Name', $entityName)
            ->open(array($entityName))
            ->edit()
            ->enableNotes('No')
            ->save()
            ->assertMessage('Entity saved');
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle')
            ->filterBy('Username', PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->open(array(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN));
        /** @var Notes $login */
        $login->openNotes('Oro\Bundle\NoteBundle')
            ->addNoteButtonNotAvailable();
    }
}
