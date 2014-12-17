/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var CommentListComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        routing = require('routing'),
        tools = require('oroui/js/tools'),
        mediator = require('oroui/js/mediator'),
        CommentListCollection = require('orocomment/js/app/models/comment-list-collection'),
        CommentModel = require('orocomment/js/app/models/comment-list-model'),
        CommentView = require('orocomment/js/app/models/comment-list-model'),
        CommentFormView = require('orocomment/js/app/views/comment-form-view')
        ;

    CommentListComponent = BaseComponent.extend({
        defaults: {
            commentListOptions: {
                configuration: {},
                urls: {
                    getList: '/app_dev.php/api/rest/{version}/commentlist/{entityClass}/{entityId}/{page}.{_format}'
                },
                routes: {},
                pager: {},
                itemView:  CommentView,
                itemModel: CommentModel
            },
            commentListData: '[]',
            commentListCount: 0,
            widgetId: '',
            modules: {},
            form: {
                url: '/app_dev.php/comments/form',
                isLoaded: false,
                view: '',
                contentHTML: ''
            }
        },

        initialize: function (options) {
            this.options = options || {};

            this.processOptions();

            this.loadForm();

            /*if (!_.isEmpty(this.options.modules)) {
                this._deferredInit();
                tools.loadModules(this.options.modules, function (modules) {
                    _.extend(this.options.commentListOptions, modules);
                    this.initView();
                    this._resolveDeferredInit();
                }, this);
            } else {
                this.loadModules();
                this.initView();
            }*/
        },
        loadModules: function () {
            var that = this;
/*
            $.ajax({url: this.options.formWidgetRoute, type: 'get', dataType: 'html'})
                .done(function (data) {
                    var response = $('<html />').html(data);
                    that._setCommentForm( $(response).find('.widget-content').html());
                })
                .fail(_.bind(that._showLoadItemsError, this));
*/
        },
        processOptions: function () {
            var defaults;
            defaults = $.extend(true, {}, this.defaults);
            _.defaults(this.options, defaults);
            _.defaults(this.options.commentListOptions, defaults.commentListOptions);
        },
        loadForm: function () {
            var that = this;

            $.ajax({url: this.options.form.url, type: 'get', dataType: 'html'})
                .done(function(data) {
                    var response = $('<html />').html(data);
                    that._setCommentForm($(response).find('.widget-content').html());
                    that.options.form.isLoaded = true;

                    that.options.form.view = new CommentFormView({
                        el: that.options._sourceElement,
                        contentHTML: that.options.form.contentHTML
                    });
                })
                .fail(_.bind(that._showLoadItemsError, this));
        },
        initView: function() {
            var commentOptions, collection;
            commentOptions = this.options.commentListOptions;

            // setup comment list collection
            collection = new CommentListCollection(this.options.commentListOptions, {
                model: commentOptions.itemModel
            });

            /*
            collection.route = activityOptions.urls.route;
            collection.routeParameters = activityOptions.urls.parameters;
            collection.setPageSize(this.options.activityListOptions.pager.pagesize);
            collection.setCount(this.options.activityListCount);

            activityOptions.collection = collection;

            // bind template for item view
            activityOptions.itemView = activityOptions.itemView.extend({
                template: _.template($(activityOptions.itemTemplate).html())
            });

            this.list = new ActivityListView(activityOptions);

            this.registerWidget();
            */
        },
        _showLoadItemsError: function (err) {
            this._showError(this.options.messages.loadItemsError, err);
        },
        _setCommentForm: function(form) {
            this.options.form.contentHTML = form;
        }
    });


    return CommentListComponent;
});
