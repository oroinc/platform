define(function(require) {
    'use strict';

    const document = window.document;
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');
    const tinyMCE = require('tinymce/tinymce');

    /**
     * @export  oroemail/js/app/views/email-variable-view
     */
    const EmailVariableView = BaseView.extend({
        options: {
            templateSelector: null,
            sectionTemplateSelector: null,
            sectionContentSelector: null,
            sectionTabSelector: null,
            fieldsSelectors: ['input[type="text"][name*="subject"]', 'textarea[name*="content"]'],
            defaultFieldIndex: 1 // index in fieldsSelectors
        },

        events: {
            'click a.variable': '_handleVariableClick',
            'click a.reference': '_handleReferenceClick'
        },

        sections: {
            system: 'system',
            entity: 'entity'
        },

        /**
        * @property {jQuery}
        */
        lastElement: null,

        /**
         * @inheritdoc
         */
        constructor: function EmailVariableView(options) {
            EmailVariableView.__super__.constructor.call(this, options);
        },

        /**
         * Constructor
         *
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.template = _.template($(this.options.templateSelector).html());
            this.systemTemplate = _.template(
                $(this._getSectionSelector(this.options.sectionTemplateSelector, this.sections.system)).html()
            );
            this.entityTemplate = _.template(
                $(this._getSectionSelector(this.options.sectionTemplateSelector, this.sections.entity)).html()
            );

            this.listenTo(this.model, 'change:entity', this._renderEntityVariables);

            this.fields = $(this.options.fieldsSelectors.join(','));
            this.fields.on('blur', this._updateElementsMetaData.bind(this));

            this.lastElement = $(this.options.fieldsSelectors[this.options.defaultFieldIndex]);
        },

        /**
         * Renders the view
         *
         * @returns {*}
         */
        render: function() {
            const vars = {system: this._getSystemVariablesHtml(this.model.getSystemVariables())};

            this.$el.empty();
            this.$el.html(this.template({variables: vars}));
            this._applyDraggable(this._getSectionContent(this.sections.system));
            this._renderEntityVariables();

            return this;
        },

        /**
         * Checks if fields has any text
         *
         * @returns {boolean}
         */
        isEmpty: function() {
            let result = true;
            _.each(this.fields, function(el) {
                if (el.value) {
                    result = false;
                }
            });
            return result;
        },

        /**
         * Sets empty string as a value for all fields
         */
        clear: function() {
            _.each(this.fields, function(el) {
                const editor = tinyMCE.get(el.id);
                if (editor) {
                    editor.setContent('');
                } else if (el.value) {
                    el.value = '';
                }
            });
        },

        /**
         * Renders section contains entity related variables
         *
         * @private
         */
        _renderEntityVariables: function() {
            const $el = this._getSectionContent(this.sections.entity);
            const $tabEl = this._getSectionTab(this.sections.entity);
            const entityVars = this.model.getEntityVariables();
            const entityLabel = this.model.getEntityLabel();
            const path = this.model.getPath();
            const pathLabels = this.model.getPathLabels();

            // remove old content
            $el.empty();

            if (_.isEmpty(entityVars) && !path) {
                // hide section and switch to 'system' section if 'entity' section is active
                if (this._isVisible($tabEl)) {
                    if ($tabEl.hasClass('active')) {
                        $tabEl.removeClass('active');
                        this._getSectionTab(this.sections.system).addClass('active');
                    }
                    $tabEl.hide();
                }
                if (this._isVisible($el)) {
                    if ($el.hasClass('active')) {
                        $el.removeClass('active');
                        this._getSectionContent(this.sections.system).addClass('active');
                    }
                    $el.hide();
                }
            } else {
                // show 'entity' section if it is invisible
                // we cannot use 'show' method here because it adds 'display: block' inline style
                // and as result both 'system' and 'entity' variables are visible in the 'system' section
                if (!this._isVisible($tabEl)) {
                    $tabEl.removeAttr('style');
                }
                if (!this._isVisible($el)) {
                    $el.removeAttr('style');
                }
                // add new content
                $el.html(this._getEntityVariablesHtml(entityVars, entityLabel, path, pathLabels));
                this._applyDraggable($el);
            }
        },

        /**
         * Checks whether the given element is visible or not
         *
         * @param {jQuery} $el
         * @returns {boolean}
         * @private
         */
        _isVisible: function($el) {
            // $el.is(':visible') cannot be used here because it is possible that this method
            // is called when the element is temporary not visible
            // for example this view is rendered when 'Loading ...' mask is not hidden yet
            return $el.css('display') !== 'none';
        },

        /**
         * @param {Array} variables
         * @returns {string}
         * @private
         */
        _getSystemVariablesHtml: function(variables) {
            return this.systemTemplate({
                variables: variables,
                root: this.sections.system
            });
        },

        /**
         * @param {Array}  variables
         * @param {string} entityLabel
         * @param {string} path
         * @param {Array}  pathLabels
         * @returns {string}
         * @private
         */
        _getEntityVariablesHtml: function(variables, entityLabel, path, pathLabels) {
            const fields = {};
            const relations = {};
            _.each(variables, function(variable, varName) {
                if (_.has(variable, 'related_entity_name')) {
                    relations[varName] = variable;
                    fields[varName] = variable;
                } else {
                    fields[varName] = variable;
                }
            });
            return this.entityTemplate({
                fields: fields,
                relations: relations,
                entityLabel: entityLabel,
                path: path,
                pathLabels: pathLabels,
                root: this.sections.entity
            });
        },

        /**
         * @param {jQuery} $el
         * @private
         */
        _applyDraggable: function($el) {
            $el.find('a.variable').on('dragstart', function(e) {
                const dt = e.originalEvent.dataTransfer;
                for (let i = 0; i < dt.types.length; i++) {
                    const type = dt.types[i];
                    dt.clearData(type);
                }
                dt.setData('text', $(e.currentTarget).text());
            });
        },

        /**
         * @param {string} selectorTemplate
         * @param {string} sectionName
         * @returns {string}
         * @private
         */
        _getSectionSelector: function(selectorTemplate, sectionName) {
            return selectorTemplate.replace(/\{sectionName\}/g, sectionName);
        },

        /**
         * @param {string} sectionName
         * @returns {jQuery}
         * @private
         */
        _getSectionTab: function(sectionName) {
            return this.$el.find(this._getSectionSelector(this.options.sectionTabSelector, sectionName));
        },

        /**
         * @param {string} sectionName
         * @returns {jQuery}
         * @private
         */
        _getSectionContent: function(sectionName) {
            return this.$el.find(this._getSectionSelector(this.options.sectionContentSelector, sectionName));
        },

        /**
         * Handle onClick event for 'variable' links
         * This method adds a variable to the last element
         *
         * @param {Event} e
         */
        _handleVariableClick: function(e) {
            let field = this.fields.filter(document.activeElement);
            const variable = $(e.currentTarget).html();

            e.preventDefault();
            if (!field.length && this.lastElement && this.lastElement.is(':visible, [data-focusable]')) {
                field = this.lastElement;
            }

            if (field.length) {
                field.insertAtCursor(variable).focus();
                mediator.trigger('email-variable-view:click-variable', field, variable);
            } else {
                mediator.execute('showFlashMessage', 'error',
                    __('oro.email.emailtemplate.cannot_insert_variable'));
            }
        },

        /**
         * Handle onClick event for 'reference' links
         * This method change the current entity path in the model
         *
         * @param {Event} e
         * @private
         */
        _handleReferenceClick: function(e) {
            const $el = $(e.currentTarget);
            const path = $el.data('path');

            this.model.setPath(path);
        },

        /**
         * Update elements metadata
         *
         * @param {Event} e
         * @private
         */
        _updateElementsMetaData: function(e) {
            this.lastElement = $(e.currentTarget);
        }
    });

    return EmailVariableView;
});
