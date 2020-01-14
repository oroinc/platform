define(function(require) {
    'use strict';

    const DemoHelpCarouselView = require('oroviewswitcher/js/app/views/demo/demo-help-carousel-view');
    const about30MinReset = require('text-loader!oroviewswitcher/templates/help-slides/about-30-min-reset.html');
    const aboutPersonalDemo = require('text-loader!oroviewswitcher/templates/help-slides/about-personal-demo.html');

    DemoHelpCarouselView.addSlide(10, about30MinReset);
    DemoHelpCarouselView.addSlide(20, aboutPersonalDemo);
});
