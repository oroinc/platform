<?php

namespace Oro\Bundle\ImapBundle\Mail\Protocol;

use Oro\Bundle\ImapBundle\Exception\SocketTimeoutException;
use Oro\Bundle\ImapBundle\Mail\Protocol\Exception\InvalidEmailFormatException;
use Zend\Mail\Storage\Exception as BaseException;

/**
 * - adds PEEK capability to Zend Imap Protocol
 * - fixes the parsing of double quotes in labels
 */
class Imap extends \Zend\Mail\Protocol\Imap
{
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function fetch($items, $from, $to = null, $uid = false)
    {
        if (is_array($from)) {
            $set = implode(',', $from);
        } elseif ($to === null) {
            $set = (int) $from;
        } elseif ($to === INF) {
            $set = (int) $from . ':*';
        } else {
            $set = (int) $from . ':' . (int) $to;
        }

        $items = (array) $items;
        $itemList = $this->escapeList($items);
        $item = str_replace('.PEEK', '', $items[0]);

        $tag = null;  // define $tag variable before first use
        $this->sendRequest('FETCH', array($set, $itemList), $tag);

        $result = array();
        $tokens = null; // define $tokens variable before first use
        while (!$this->readLine($tokens, $tag)) {
            // ignore other responses
            if ($tokens[1] != 'FETCH') {
                continue;
            }
            // ignore other messages
            if ($to === null && !is_array($from) && $tokens[0] != $from) {
                continue;
            }
            // if we only want one item we return that one directly
            if (count($items) == 1) {
                if ($tokens[2][0] == $item) {
                    $data = $tokens[2][1];
                } else {
                    // maybe the server send an other field we didn't wanted
                    $count = count($tokens[2]);
                    // we start with 2, because 0 was already checked
                    for ($i = 2; $i < $count; $i += 2) {
                        if ($tokens[2][$i] != $item) {
                            continue;
                        }
                        $data = $tokens[2][$i + 1];
                        break;
                    }
                }
            } else {
                $data = array();
                while (key($tokens[2]) !== null) {
                    $data[current($tokens[2])] = next($tokens[2]);
                    next($tokens[2]);
                }
            }
            // if we want only one message we can ignore everything else and just return
            if ($to === null && !is_array($from) && $tokens[0] == $from) {
                // we still need to read all liness
                while (!$this->readLine($tokens, $tag)) {
                }
                return $data;
            }
            $result[$tokens[0]] = $data;
        }

        if ($to === null && !is_array($from)) {
            if ($tokens[0] === 'OK' && $tokens[1] === 'FETCH' && $tokens[2] === 'completed.') {
                throw new InvalidEmailFormatException('Invalid email format');
            }
            throw new BaseException\RuntimeException('the single id was not found in response');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function connect($host, $port = null, $ssl = false)
    {
        parent::connect($host, $port, $ssl);

        // Server configuration should decide, which timeout value has to be used.
        stream_set_timeout($this->socket, ini_get('default_socket_timeout'));
    }

    // @codingStandardsIgnoreStart
    /**
     * {@inheritdoc}
     */
    protected function _nextLine()
    {
        $line = fgets($this->socket);
        if ($line === false) {
            throw new SocketTimeoutException(
                'cannot read - connection closed?',
                0,
                null,
                stream_get_meta_data($this->socket)
            );
        }

        return $line;
    }
    // @codingStandardsIgnoreEnd

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function decodeLine($line)
    {
        $tokens = [];
        $stack = [];

        /*
            We start to decode the response here. The understood tokens are:
                literal
                "literal" or also "lit\\er\"al"
                {bytes}<NL>literal
                (literals*)
            All tokens are returned in an array. Literals in braces (the last understood
            token in the list) are returned as an array of tokens. I.e. the following response:
                "foo" baz {3}<NL>bar ("f\\\"oo" bar)
            would be returned as:
                array('foo', 'baz', 'bar', array('f\\\"oo', 'bar'));
        */
        //  replace any trailing <NL> including spaces with a single space
        $line = rtrim($line) . ' ';
        while (($pos = strpos($line, ' ')) !== false) {
            $token = substr($line, 0, $pos);
            if (! strlen($token)) {
                continue;
            }
            while ($token[0] == '(') {
                array_push($stack, $tokens);
                $tokens = [];
                $token = substr($token, 1);
            }
            if ($token[0] == '"') {
                if (preg_match('%^\(*"((.|\\\\|\\")*?)(?<!\\\\)" *%', $line, $matches)) {
                    $tokens[] = $matches[1];
                    $line = substr($line, strlen($matches[0]));
                    continue;
                }
            }
            if ($token[0] == '{') {
                $endPos = strpos($token, '}');
                $chars = substr($token, 1, $endPos - 1);
                if (is_numeric($chars)) {
                    $token = '';
                    while (strlen($token) < $chars) {
                        $token .= $this->nextLine();
                    }
                    $line = '';
                    if (strlen($token) > $chars) {
                        $line = substr($token, $chars);
                        $token = substr($token, 0, $chars);
                    } else {
                        $line .= $this->nextLine();
                    }
                    $tokens[] = $token;
                    $line = trim($line) . ' ';
                    continue;
                }
            }
            if ($stack && $token[strlen($token) - 1] == ')') {
                // closing braces are not separated by spaces, so we need to count them
                $braces = strlen($token);
                $token = rtrim($token, ')');
                // only count braces if more than one
                $braces -= strlen($token) + 1;
                // only add if token had more than just closing braces
                if (rtrim($token) != '') {
                    $tokens[] = rtrim($token);
                }
                $token = $tokens;
                $tokens = array_pop($stack);
                // special handline if more than one closing brace
                while ($braces-- > 0) {
                    $tokens[] = $token;
                    $token = $tokens;
                    $tokens = array_pop($stack);
                }
            }
            $tokens[] = $token;
            $line = substr($line, $pos + 1);
        }

        // maybe the server forgot to send some closing braces
        while ($stack) {
            $child = $tokens;
            $tokens = array_pop($stack);
            $tokens[] = $child;
        }

        return $tokens;
    }

    /**
     * {@inheritdoc}
     */
    public function listMailbox($reference = '', $mailbox = '*')
    {
        $result = [];
        $list = $this->requestAndResponse('LIST', $this->escapeString($reference, $mailbox));
        if (! $list || $list === true) {
            return $result;
        }

        foreach ($list as $item) {
            if (count($item) != 4 || $item[0] != 'LIST') {
                continue;
            }

            $result[stripcslashes($item[3])] = ['delim' => $item[2], 'flags' => $item[1]];
        }

        return $result;
    }
}
