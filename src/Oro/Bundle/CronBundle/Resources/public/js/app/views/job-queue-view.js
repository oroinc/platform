define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const tools = require('oroui/js/tools');
    const BaseView = require('oroui/js/app/views/base/view');

    const JobQueueView = BaseView.extend({
        events: {
            'click [data-action-name=run-daemon]': 'changeDemonState',
            'click [data-action-name=stop-daemon]': 'changeDemonState',
            'click .stack-trace a': 'toggleStateTrace'
        },

        intervalUpdate: 10000,

        intervalId: null,

        /**
         * @inheritdoc
         */
        constructor: function JobQueueView(options) {
            JobQueueView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            _.extend(this, _.pick(options, ['intervalUpdate']));
            this.intervalId = setInterval(this.checkStatus.bind(this), this.intervalUpdate);
            JobQueueView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            if (this.intervalId) {
                clearInterval(this.intervalId);
                this.intervalId = null;
            }
            JobQueueView.__super__.dispose.call(this);
        },

        /**
         * Handles click on stop/run daemon buttons
         *
         * @param {jQuery.Event} e
         */
        changeDemonState: function(e) {
            e.preventDefault();

            const $link = this.$(e.currentTarget);
            const $loader = this.getActionElement('status').closest('div').find('img');

            $loader.show();

            $.getJSON($link.attr('href'), data => {
                if (!data.error) {
                    $link.closest('div')
                        .find('span:first')
                        .toggleClass('label-success label-important')
                        .text(tools.isNumeric(data.message) ? __('Running') : __('Not running'))
                        .end()
                        .closest('div').find('span:last').text(data.message).end();

                    this.updateButtons(!tools.isNumeric(data.message));
                }

                $loader.hide();
            });
        },

        /**
         * Toggle/expands state trace
         *
         * @param {jQuery.Event} e
         */
        toggleStateTrace: function(e) {
            e.preventDefault();

            const $link = this.$(e.currentTarget);
            const $traces = $link.closest('.stack-trace').find('.traces');

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
        checkStatus: function() {
            const $statusLink = this.getActionElement('status');
            const $loader = $statusLink.closest('div').find('img');
            if (!$statusLink.length) {
                return;
            }

            $loader.show();

            $.get($statusLink.attr('href'), data => {
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
            });
        },

        /**
         * Fetches action element (stop|run|status)
         *
         * @param {string} action
         * @returns {jQuery.Element}
         */
        getActionElement: function(action) {
            return this.$('[data-action-name="' + action + '-daemon"]');
        },

        /**
         * Updates stop/run daemon buttons
         *
         * @param {boolean} run
         */
        updateButtons: function(run) {
            this.getActionElement('run').toggle(run);
            this.getActionElement('stop').toggle(!run);
        }
    });

    return JobQueueView;
});
