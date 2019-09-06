define(function(require) {
    'use strict';

    var DemoHelpCarouselView = require('oroviewswitcher/js/app/views/demo/demo-help-carousel-view');
    var about30MinReset = require('text!oroviewswitcher/templates/help-slides/about-30-min-reset.html');
    var aboutPersonalDemo = require('text!oroviewswitcher/templates/help-slides/about-personal-demo.html');

    DemoHelpCarouselView.addSlide(10, about30MinReset);
    DemoHelpCarouselView.addSlide(20, aboutPersonalDemo);
});
