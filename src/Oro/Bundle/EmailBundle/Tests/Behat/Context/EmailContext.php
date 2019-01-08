<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\EmailBundle\Tests\Behat\Mock\Mailer\DirectMailerDecorator;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\AssertTrait;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EmailContext extends OroFeatureContext implements KernelAwareContext
{
    use AssertTrait, KernelDictionary;

    /** @var DirectMailerDecorator */
    private $mailer;

    /**
     * @BeforeScenario
     * @AfterScenario
     */
    public function clear()
    {
        $mailer = $this->getMailer();
        if ($mailer instanceof DirectMailerDecorator) {
            $mailer->clear();
        }
    }

    /**
     * @Given /^Email should contains the following "([^"]*)" text$/
     * @Given /^An email containing the following "([^"]*)" text was sent$/
     *
     * @param string $text
     */
    public function emailShouldContainsTheFollowingText($text)
    {
        self::assertNotEmpty($text, 'Assertion text can\'t be empty.');

        $mailer = $this->getMailer();
        if (!$mailer instanceof DirectMailerDecorator) {
            return;
        }

        $pattern = $this->getPattern($text);
        $found = false;

        /** @var \Swift_Mime_Message $message */
        foreach ($mailer->getSentMessages() as $message) {
            $data = array_map(
                function ($field) use ($message) {
                    return $this->getMessageData($message, $field);
                },
                ['From', 'To', 'Cc', 'Bcc', 'Subject', 'Body']
            );

            $found = (bool) preg_match($pattern, implode(' ', $data));
            if ($found !== false) {
                break;
            }
        }

        self::assertNotFalse($found, 'Sent emails bodies don\'t contain expected text.');
    }

    /**
     * Example: Then Email should contains the following:
     *            | From    | admin@example.com |
     *            | To      | user1@example.com |
     *            | Cc      | user2@example.com |
     *            | Bcc     | user3@example.com |
     *            | Subject | Test Subject      |
     *            | Body    | Test Body         |
     *
     * @Given /^Email should contains the following:/
     * @Given /^An email containing the following was sent:/
     *
     * @param TableNode $table
     */
    public function emailShouldContainsTheFollowing(TableNode $table)
    {
        self::assertNotEmpty($table, 'Assertions list must contain at least one row.');

        $mailer = $this->getMailer();
        if (!$mailer instanceof DirectMailerDecorator) {
            return;
        }

        $expectedRows = [];
        foreach ($table->getRows() as list($field, $text)) {
            //Keys makes possible to use multiple Body field in expected table
            $expectedRows[] = ['field' => $field, 'pattern' => $this->getPattern($text)];
        }

        $sentMessages = $mailer->getSentMessages();

        self::assertNotEmpty($sentMessages, 'There are no sent messages');

        $found = false;
        /** @var \Swift_Mime_Message $message */
        foreach ($sentMessages as $message) {
            foreach ($expectedRows as $expectedContent) {
                $found = (bool) preg_match(
                    $expectedContent['pattern'],
                    $this->getMessageData($message, $expectedContent['field'])
                );
                if ($found === false) {
                    break;
                }
            }

            if ($found) {
                break;
            }
        }

        if (!$found) {
            $messagesData = [];
            foreach ($mailer->getSentMessages() as $message) {
                $item = [];
                foreach ($expectedRows as $expectedContent) {
                    $item[$expectedContent['field']] = $this->getMessageData($message, $expectedContent['field']);
                }
                $messagesData[] = $item;
            }

            self::fail(
                sprintf(
                    'Sent emails don\'t contain expected data. The following messages has been sent: %s',
                    print_r($messagesData, true)
                )
            );
        }
    }

    /**
     * Example: Then Email should not contains the following:
     *            | From    | admin@example.com |
     *            | To      | user1@example.com |
     *            | Cc      | user2@example.com |
     *            | Bcc     | user3@example.com |
     *            | Subject | Test Subject      |
     *            | Body    | Test Body         |
     *
     * @Given /^Email should not contains the following:/
     * @Given /^An email does not containing the following was sent:/
     *
     * @param TableNode $table
     */
    public function emailShouldNotContainsTheFollowing(TableNode $table)
    {
        self::assertNotEmpty($table, 'Assertions list must contain at least one row.');

        $mailer = $this->getMailer();
        if (!$mailer instanceof DirectMailerDecorator) {
            return;
        }

        $expectedRows = [];
        foreach ($table->getRows() as list($field, $text)) {
            //Keys makes possible to use multiple Body field in expected table
            $expectedRows[] = ['field' => $field, 'pattern' => $this->getPattern($text)];
        }

        $sentMessages = $mailer->getSentMessages();

        self::assertNotEmpty($sentMessages, 'There are no sent messages');

        $found = false;
        /** @var \Swift_Mime_Message $message */
        foreach ($sentMessages as $message) {
            foreach ($expectedRows as $expectedContent) {
                $found = (bool) preg_match(
                    $expectedContent['pattern'],
                    $this->getMessageData($message, $expectedContent['field'])
                );
                if ($found === false) {
                    break;
                }
            }

            if ($found) {
                break;
            }
        }

        if ($found) {
            $messagesData = [];
            foreach ($mailer->getSentMessages() as $message) {
                $item = [];
                foreach ($expectedRows as $expectedContent) {
                    $item[$expectedContent['field']] = $this->getMessageData($message, $expectedContent['field']);
                }
                $messagesData[] = $item;
            }

            self::fail(
                sprintf(
                    'Sent emails contains extra data. The following messages has been sent: %s',
                    print_r($messagesData, true)
                )
            );
        }
    }

    /**
     * Example: Then email with Subject "Your RFQ has been received." containing the following was sent:
     *            | From    | admin@example.com |
     *            | To      | user1@example.com |
     *            | Cc      | user2@example.com |
     *            | Bcc     | user3@example.com |
     *            | Body    | Test Body         |
     *
     * @Given /^email with (?P<searchField>[\w]+) "(?P<searchText>(?:[^"]|\\")*)" containing the following was sent:/
     *
     * @param string $searchField
     * @param string $searchText
     * @param TableNode $table
     */
    public function emailWithFieldMustContainsTheFollowing(string $searchField, string $searchText, TableNode $table)
    {
        self::assertNotEmpty($table, 'Assertions list must contain at least one row.');

        self::assertEmailFieldValid($searchField);

        $mailer = $this->getMailer();
        if (!$mailer instanceof DirectMailerDecorator) {
            return;
        }

        $expectedContent = [];
        foreach ($table->getRows() as list($field, $text)) {
            $expectedContent[$field] = $this->getPattern($text);
        }

        $found = false;

        /** @var \Swift_Mime_Message $message */
        foreach ($mailer->getSentMessages() as $message) {
            if ($searchText !== $this->getMessageData($message, $searchField)) {
                continue;
            }

            foreach ($expectedContent as $field => $pattern) {
                $found = (bool) preg_match($pattern, $this->getMessageData($message, $field));
                if ($found === false) {
                    break 2;
                }
            }
        }

        self::assertNotFalse($found, 'Sent emails don\'t contain expected data.');
    }

    /**
     * Example: Then email with Subject "Your RFQ has been received." was not sent:
     *
     * @Given /^email with (?P<searchField>[\w]+) "(?P<searchText>(?:[^"]|\\")*)" was not sent/
     *
     * @param string $searchField
     * @param string $searchText
     */
    public function emailWithFieldIsNotSent(string $searchField, string $searchText)
    {
        self::assertEmailFieldValid($searchField);

        $mailer = $this->getMailer();
        if (!$mailer instanceof DirectMailerDecorator) {
            return;
        }

        /** @var \Swift_Mime_Message $message */
        foreach ($mailer->getSentMessages() as $message) {
            if ($searchText === $this->getMessageData($message, $searchField)) {
                self::fail(sprintf('Email with %s \"%s\" was not expected to be sent', $searchField, $searchText));
            }
        }
    }

    /**
     * @param string $text
     * @return string
     */
    private function getPattern($text)
    {
        return sprintf('/%s/', preg_replace('/\s+/', '[[:space:][:cntrl:]]+', preg_quote($text, '/')));
    }

    /**
     * @param \Swift_Mime_Message $message
     * @param string $field
     * @return string
     */
    private function getMessageData(\Swift_Mime_Message $message, $field)
    {
        switch (strtolower(trim($field))) {
            case 'from':
                $data = array_keys($message->getFrom());
                break;
            case 'to':
                $data = array_keys($message->getTo());
                break;
            case 'cc':
                $data = is_array($message->getCc()) ? array_keys($message->getCc()) : $message->getCc();
                break;
            case 'bcc':
                $data = is_array($message->getBcc()) ? array_keys($message->getBcc()) : $message->getBcc();
                break;
            case 'subject':
                $data = $message->getSubject();
                break;
            case 'body':
                $data = strip_tags($message->getBody());
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported email field "%s".', $field));
                break;
        }

        $messageData = \is_array($data) ? implode(' ', $data) : $data;

        return trim(strip_tags($messageData));
    }

    /**
     * @return DirectMailer
     */
    private function getMailer()
    {
        if (!$this->mailer) {
            $this->mailer = $this->getContainer()->get('oro_email.direct_mailer');
        }

        return $this->mailer;
    }

    /**
     * @param string $fieldName
     */
    private static function assertEmailFieldValid(string $fieldName): void
    {
        $allowedFields = ['From', 'To', 'Cc', 'Bcc', 'Subject', 'Body'];
        self::assertContains(
            $fieldName,
            $allowedFields,
            'Email field must be one of '.implode(', ', $allowedFields)
        );
    }
}
