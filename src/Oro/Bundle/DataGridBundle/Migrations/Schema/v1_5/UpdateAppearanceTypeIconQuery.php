<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Schema\v1_5;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateAppearanceTypeIconQuery extends ParametrizedMigrationQuery
{
    /** @var array */
    private static $iconReplaceMask = [
        '^icon-*' => ['fa-', 'next' => true],

        'ban-circle' => 'ban',
        'bar-chart' => 'bar-chart-o',
        'beaker' => 'flask',
        'bell' => 'bell-o',
        'bell-alt' => 'bell',
        'bitbucket-sign' => 'bitbucket-square',
        'bookmark-empty' => 'bookmark-o',
        'building' => 'building-o', // (4.0.2)
        'calendar-empty' => 'calendar-o',
        'check-empty' => 'square-o',
        'check-minus' => 'minus-square-o',
        'check-sign' => 'check-square',
        'check' => 'check-square-o',
        'chevron-sign-down' => 'chevron-down',
        'chevron-sign-left' => 'chevron-left',
        'chevron-sign-right' => 'chevron-right',
        'chevron-sign-up' => 'chevron-up',
        'circle-arrow-down' => 'arrow-circle-down',
        'circle-arrow-left' => 'arrow-circle-left',
        'circle-arrow-right' => 'arrow-circle-right',
        'circle-arrow-up' => 'arrow-circle-up',
        'circle-blank' => 'circle-o',
        'cny' => 'rmb',
        'collapse-alt' => 'minus-square-o',
        'collapse-top' => 'caret-square-o-up',
        'collapse' => 'caret-square-o-down',
        'comment-alt' => 'comment-o',
        'comments-alt' => 'comments-o',
        'copy' => 'files-o',
        'cut' => 'scissors',
        'dashboard' => 'tachometer',
        'double-angle-down' => 'angle-double-down',
        'double-angle-left' => 'angle-double-left',
        'double-angle-right' => 'angle-double-right',
        'double-angle-up' => 'angle-double-up',
        'download' => 'arrow-circle-o-down',
        'download-alt' => 'download',
        'edit-sign' => 'pencil-square',
        'edit' => 'pencil-square-o',
        'ellipsis-horizontal' => 'ellipsis-h', // (4.0.2)
        'ellipsis-vertical' => 'ellipsis-v', // (4.0.2)
        'envelope-alt' => 'envelope-o',
        'exclamation-sign' => 'exclamation-circle',
        'expand-alt' => 'plus-square-o', // (4.0.2)
        'expand' => 'caret-square-o-right',
        'external-link-sign' => 'external-link-square',
        'eye-close' => 'eye-slash',
        'eye-open' => 'eye',
        'facebook-sign' => 'facebook-square',
        'facetime-video' => 'video-camera',
        'file-alt' => 'file-o',
        'file-text-alt' => 'file-text-o',
        'flag-alt' => 'flag-o',
        'folder-close-alt' => 'folder-o',
        'folder-close' => 'folder',
        'folder-open-alt' => 'folder-open-o',
        'food' => 'cutlery',
        'frown' => 'frown-o',
        'fullscreen' => 'arrows-alt', // (4.0.2)
        'github-sign' => 'github-square',
        'google-plus-sign' => 'google-plus-square',
        'group' => 'users', // (4.0.2)
        'h-sign' => 'h-square',
        'hand-down' => 'hand-o-down',
        'hand-left' => 'hand-o-left',
        'hand-right' => 'hand-o-right',
        'hand-up' => 'hand-o-up',
        'hdd' => 'hdd-o', // (4.0.1)
        'heart-empty' => 'heart-o',
        'hospital' => 'hospital-o', // (4.0.2)
        'indent-left' => 'outdent',
        'indent-right' => 'indent',
        'info-sign' => 'info-circle',
        'keyboard' => 'keyboard-o',
        'legal' => 'gavel',
        'lemon' => 'lemon-o',
        'lightbulb' => 'lightbulb-o',
        'linkedin-sign' => 'linkedin-square',
        'meh' => 'meh-o',
        'microphone-off' => 'microphone-slash',
        'minus-sign-alt' => 'minus-square',
        'minus-sign' => 'minus-circle',
        'mobile-phone' => 'mobile',
        'moon' => 'moon-o',
        'move' => 'arrows', // (4.0.2)
        'off' => 'power-off',
        'ok-circle' => 'check-circle-o',
        'ok-sign' => 'check-circle',
        'ok' => 'check',
        'paper-clip' => 'paperclip',
        'paste' => 'clipboard',
        'phone-sign' => 'phone-square',
        'picture' => 'picture-o',
        'pinterest-sign' => 'pinterest-square',
        'play-circle' => 'play-circle-o',
        'play-sign' => 'play-circle',
        'plus-sign-alt' => 'plus-square',
        'plus-sign' => 'plus-circle',
        'pushpin' => 'thumb-tack',
        'question-sign' => 'question-circle',
        'remove-circle' => 'times-circle-o',
        'remove-sign' => 'times-circle',
        'remove' => 'times',
        'reorder' => 'bars', // (4.0.2)
        'resize-full' => 'expand', // (4.0.2)
        'resize-horizontal' => 'arrows-h', // (4.0.2)
        'resize-small' => 'compress', // (4.0.2)
        'resize-vertical' => 'arrows-v', // (4.0.2)
        'rss-sign' => 'rss-square',
        'save' => 'floppy-o',
        'screenshot' => 'crosshairs',
        'share-alt' => 'share',
        'share-sign' => 'share-square',
        'share' => 'share-square-o',
        'sign-blank' => 'square',
        'signin' => 'sign-in',
        'signout' => 'sign-out',
        'smile' => 'smile-o',
        'sort-by-alphabet-alt' => 'sort-alpha-desc',
        'sort-by-alphabet' => 'sort-alpha-asc',
        'sort-by-attributes-alt' => 'sort-amount-desc',
        'sort-by-attributes' => 'sort-amount-asc',
        'sort-by-order-alt' => 'sort-numeric-desc',
        'sort-by-order' => 'sort-numeric-asc',
        'sort-down' => 'sort-desc',
        'sort-up' => 'sort-asc',
        'stackexchange' => 'stack-overflow',
        'star-empty' => 'star-o',
        'star-half-empty' => 'star-half-o',
        'sun' => 'sun-o',
        'thumbs-down-alt' => 'thumbs-o-down',
        'thumbs-up-alt' => 'thumbs-o-up',
        'time' => 'clock-o',
        'trash' => 'trash-o',
        'tumblr-sign' => 'tumblr-square',
        'twitter-sign' => 'twitter-square',
        'unlink' => 'chain-broken',
        'upload' => 'arrow-circle-o-up',
        'upload-alt' => 'upload',
        'warning-sign' => 'exclamation-triangle',
        'xing-sign' => 'xing-square',
        'youtube-sign' => 'youtube-square',
        'zoom-in' => 'search-plus',
        'zoom-out' => 'search-minus',
    ];

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->migrateConfigs($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->migrateConfigs($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function migrateConfigs(LoggerInterface $logger, $dryRun = false)
    {
        $sql = 'SELECT name, icon FROM oro_grid_appearance_type';
        $this->logQuery($logger, $sql);

        $rows = $this->connection->fetchAll($sql);

        foreach ($rows as $key => $row) {
            if (isset($row['icon'])) {
                $newIconName = $this->createIconName($row['icon']);

                if ($newIconName !== $row['icon']) {
                    $row['icon'] = $newIconName;

                    $query = 'UPDATE oro_grid_appearance_type SET icon = ? WHERE name = ?';
                    $params = [$row['icon'], $row['name']];

                    $this->logQuery($logger, $query, $params);
                    if (!$dryRun) {
                        $this->connection->executeQuery($query, $params);
                    }
                }
            }
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function createIconName($name)
    {
        foreach (self::$iconReplaceMask as $pattern => $replace) {
            $exact = (substr($pattern, -1) === '*' ? '': '$');
            $pattern = '/' . $pattern . $exact .'/';
            $next = false;

            if (is_array($replace) && $replace['next']) {
                $replace = $replace[0];
                $next = true;
            }

            if (preg_match($pattern, $name)) {
                $result = preg_replace($pattern, $replace, $name);

                if ($next) {
                    $name = $result;
                } else {
                    return $result;
                }
            }
        }

        return $name;
    }
}
