define(function (require) {
    'use strict';

    var JobQueueView,
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        BaseView = require('oroui/js/app/views/base/view');

    JobQueueView = BaseView.extend({
        events :{
            'click [data-action-name=run-daemon]': 'changeDemonState',
            'click [data-action-name=stop-daemon]': 'changeDemonState',
            'click .stack-trace a': 'toggleStateTrace'
        },

        intervalUpdate: 10000,

        intervalId: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            _.extend(this, _.pick(options, ['intervalUpdate']));
            this.intervalId = setInterval(_.bind(this.checkStatus, this), this.intervalUpdate);
            JobQueueView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }
            if (this.intervalId) {
                clearInterval(this.intervalId);
                this.intervalId = null;
            }
            JobQueueView.__super__.dispose.apply(this, arguments);
        },

        /**
         * Handles click on stop/run daemon buttons
         *
         * @param {jQuery.Event} e
         */
        changeDemonState: function (e) {
            var $link, $loader;
            e.preventDefault();

            $link = this.$(e.currentTarget);
            $loader = this.getActionElement('status').closest('div').find('img');

            $loader.show();

            $.getJSON($link.attr('href'), _.bind(function (data) {
                if (data.error) {
                    alert(data.message);
                } else {
                    $link.closest('div')
                            .find('span:first')
                                .toggleClass('label-success label-important')
                                .text($.isNumeric(data.message) ? __('Running') : __('Not running'))
                            .end()
                        .closest('div').find('span:last').text(data.message).end();

                    this.updateButtons(!$.isNumeric(data.message));
                }

                $loader.hide();
            }, this));
        },

        /**
         * Toggle/expands state trace
         *
         * @param {jQuery.Event} e
         */
        toggleStateTrace: function (e) {
            var $link, $traces;
            e.preventDefault();

            $link = this.$(e.currentTarget);
            $traces = $link.closest('.stack-trace').find('.traces');

            if ($link.next('.trace').length) {
                $link.next('.trace').toggle();
            } else {
                this.$('.traces').hide();
                $traces.toggle(!$traces.is(':visible'));
            }

            $link.find('img').toggleClass('hide');
        },

        /**
         * Checks state of the daemon
         */
        checkStatus: function () {
            var $statusLink = this.getActionElement('status'),
                $loader = $statusLink.closest('div').find('img');
            if (!$statusLink.length) {
                return;
            }

            $loader.show();

            $.get($statusLink.attr('href'), _.bind(function (data) {
                data = parseInt(data, 10);

                $statusLink
                    .closest('div')
                        .find('span:first')
                            .removeClass(data > 0 ? 'label-important' : 'label-success')
                            .addClass(data > 0 ? 'label-success' : 'label-important')
                            .text(data > 0 ? __('Running') : __('Not running'))
                        .end()
                    .closest('div').find('span:last').text(data > 0 ? data : __('N/A')).end();

                this.updateButtons(!data);

                $loader.hide();
            }, this));
        },

        /**
         * Fetches action element (stop|run|status)
         *
         * @param {string} action
         * @returns {jQuery.Element}
         */
        getActionElement: function (action) {
            return this.$('[data-action-name="' + action + '-daemon"]');
        },

        /**
         * Updates stop/run daemon buttons
         *
         * @param {boolean} run
         */
        updateButtons: function (run) {
            this.getActionElement('run').toggle(run);
            this.getActionElement('stop').toggle(!run);
        }
    });

    return JobQueueView;
});
