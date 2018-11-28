<?php

namespace Oro\Bundle\ImapBundle\Mail\Protocol;

use Oro\Bundle\ImapBundle\Exception\SocketTimeoutException;
use Oro\Bundle\ImapBundle\Mail\Protocol\Exception\InvalidEmailFormatException;
use Zend\Mail\Storage\Exception as BaseException;

/**
 * Class Imap. Add PEEK capability to Zend Imap Protocol.
 **/
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
}
