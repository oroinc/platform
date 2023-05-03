const lockers = {};
let isScrollLocked = false;

const scrollUpdate = () => {
    if (isScrollLocked === Object.keys(lockers).length > 0) {
        // not all lockers are released
        return;
    }

    isScrollLocked = !isScrollLocked;
    if (isScrollLocked) {
        const scrollY = window.scrollY;
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollY}px`;
    } else {
        const scrollY = document.body.style.top;
        document.body.style.position = '';
        document.body.style.top = '';
        window.scrollTo(0, parseInt(scrollY || '0') * -1);
    }
};

export default {
    addLocker(cid) {
        lockers[cid] = true;
        scrollUpdate();
    },

    removeLocker(cid) {
        delete lockers[cid];
        scrollUpdate();
    }
};
