import sync from 'orosync/js/sync';

export default function() {
    sync.subscribe('oro/ping', () => {});
};
