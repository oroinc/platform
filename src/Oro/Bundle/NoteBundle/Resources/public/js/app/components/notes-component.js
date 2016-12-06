define(function(require) {
    'use strict';

    var NotesComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var tools = require('oroui/js/tools');
    var mediator = require('oroui/js/mediator');
    var NoteView = require('../views/note-view');
    var NotesView = require('../views/notes-view');
    var NoteModel = require('../models/note-model');
    var NotesCollection = require('../models/notes-collection');
    require('jquery');

    NotesComponent = BaseComponent.extend({
        defaults: {
            notesOptions: {
                urls: {},
                routes: {},
                itemView: NoteView,
                itemModel: NoteModel
            },
            notesData: '[]',
            widgetId: '',
            modules: {}
        },

        initialize: function(options) {
            options = options || {};
            this.processOptions(options);

            if (!_.isEmpty(options.modules)) {
                this._deferredInit();
                tools.loadModules(options.modules, function(modules) {
                    _.extend(options.notesOptions, modules);
                    this.initView(options);
                    this._resolveDeferredInit();
                }, this);
            } else {
                this.initView(options);
            }
        },

        processOptions: function(options) {
            var defaults;
            defaults = $.extend(true, {}, this.defaults);
            _.defaults(options, defaults);
            _.defaults(options.notesOptions, defaults.notesOptions);

            // map item routes to action url function
            _.each(options.notesOptions.routes, function(route, name) {
                options.notesOptions.urls[name + 'Item'] = function(model) {
                    return routing.generate(route, {'id': model.get('id')});
                };
            });

            delete options.notesOptions.routes;
            options.notesData = JSON.parse(options.notesData);
            options.notesOptions.el = options._sourceElement;

            // collect modules which should be loaded before initialization
            _.each(['itemView', 'itemModel'], function(name) {
                if (typeof options.notesOptions[name] === 'string') {
                    options.modules[name] = options.notesOptions[name];
                }
            });
        },

        initView: function(options) {
            var notesOptions = options.notesOptions;

            // setup notes collection
            var collection = new NotesCollection(options.notesData, {
                model: notesOptions.itemModel
            });
            collection.baseUrl = notesOptions.urls.list;
            notesOptions.collection = collection;

            // bind template for item view
            notesOptions.itemView = notesOptions.itemView.extend({
                template: _.template($(notesOptions.itemTemplate).html())
            });

            this.list = new NotesView(notesOptions);
            this.registerWidget(options);
        },

        registerWidget: function(options) {
            var list = this.list;
            mediator.execute('widgets:getByIdAsync', options.widgetId, function(widget) {
                widget.getAction('collapse_all', 'adopted', function(action) {
                    action.on('click', _.bind(list.collapseAll, list));
                });
                widget.getAction('refresh', 'adopted', function(action) {
                    action.on('click', _.bind(list.refresh, list));
                });
                widget.getAction('toggle_sorting', 'adopted', function(action) {
                    action.on('click', _.bind(list.toggleSorting, list));
                });
            });
        }
    });

    return NotesComponent;
});
