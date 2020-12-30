import Backbone from 'backbone';

class Progress {
    constructor(steps) {
        this.todo = steps;
        this.done = 0;
    }

    step() {
        this.done++;
        const progress = this.progress();
        this.trigger('progress', progress);
        if (progress === 100) {
            this.trigger('done');
        }
    }

    progress() {
        const {done, todo} = this;
        return Math.round(done / todo * 100);
    }
}

Object.setPrototypeOf(Progress.prototype, Backbone.Events);

export default Progress;
