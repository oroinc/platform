import DemoHelpCarouselView from 'oroviewswitcher/js/app/views/demo/demo-help-carousel-view';
import about30MinReset from 'text-loader!oroviewswitcher/templates/help-slides/about-30-min-reset.html';
import aboutPersonalDemo from 'text-loader!oroviewswitcher/templates/help-slides/about-personal-demo.html';

DemoHelpCarouselView.addSlide(10, about30MinReset);
DemoHelpCarouselView.addSlide(20, aboutPersonalDemo);
