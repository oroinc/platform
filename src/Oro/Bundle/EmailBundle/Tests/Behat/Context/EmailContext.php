<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\EmailBundle\Tests\Behat\Mock\Mailer\DirectMailerDecorator;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\AssertTrait;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

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

            $found = preg_match($pattern, implode(' ', $data));
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

        $expectedContent = [];
        foreach ($table->getRows() as list($field, $text)) {
            $expectedContent[$field] = $this->getPattern($text);
        }

        $found = false;

        /** @var \Swift_Mime_Message $message */
        foreach ($mailer->getSentMessages() as $message) {
            foreach ($expectedContent as $field => $pattern) {
                $found = preg_match($pattern, $this->getMessageData($message, $field));
                if ($found === false) {
                    break;
                }
            }
        }

        self::assertNotFalse($found, 'Sent emails don\'t contain expected data.');
    }

    /**
     * @param string $text
     * @return string
     */
    private function getPattern($text)
    {
        return sprintf('/%s/', preg_replace('/\s+/', '[[:space:][:cntrl:]]+', $text));
    }

    /**
     * @param \Swift_Mime_Message $message
     * @param string $field
     * @return string
     */
    private function getMessageData(\Swift_Mime_Message $message, $field)
    {
        switch (trim(strtolower($field))) {
            case 'from':
                $data = $message->getFrom();
                break;
            case 'to':
                $data = $message->getTo();
                break;
            case 'cc':
                $data = $message->getCc();
                break;
            case 'bcc':
                $data = $message->getBcc();
                break;
            case 'subject':
                $data = $message->getSubject();
                break;
            case 'body':
                $data = $message->getBody();
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported email field "%s".', $field));
                break;
        }

        return is_array($data) ? implode(' ', $data) : $data;
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
}
