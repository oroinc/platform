import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';

const AttachmentView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['emptyFileSelector', 'fileSelector', 'isExternalFile']),

    events: {
        'click [data-role="remove"]': 'onRemoveAttachment'
    },

    /**
     * @property {String}
     */
    emptyFileSelector: '',

    /**
     * @property {String}
     */
    fileSelector: '',

    /**
     * @property {Boolean}
     */
    isExternalFile: false,

    /**
     * @property {jQuery.Element}
     */
    $file: null,

    /**
     * @property {jQuery.Element}
     */
    $emptyFile: null,

    /**
     * @property {String}
     */
    originalUrl: null,

    /**
     * @inheritdoc
     */
    constructor: function AttachmentView(options) {
        AttachmentView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        AttachmentView.__super__.initialize.call(this, options);

        this.$emptyFile = $(this.emptyFileSelector);
        this.$file = $(this.fileSelector);

        if (this.isExternalFile) {
            this.$file.on('change', this.onUrlChange.bind(this));
            this.originalUrl = this.$file.val();
        }
    },

    /**
     * @param {jQuery.Event} e
     */
    onRemoveAttachment: function(e) {
        e.preventDefault();
        this.$el.hide();
        this.$emptyFile.val('1');

        if (this.isExternalFile) {
            this.$file.val('');
        }
    },

    /**
     * @param {jQuery.Event} e
     */
    onUrlChange: function(e) {
        this.$el.hide();

        this.$emptyFile.val(this.$file.val() ? null : '1');

        if (this.$file.val() === this.originalUrl) {
            this.$el.show();
        }
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        this.$file.off('change', this.onUrlChange.bind(this));

        AttachmentView.__super__.dispose.call(this);
    }
});

export default AttachmentView;
