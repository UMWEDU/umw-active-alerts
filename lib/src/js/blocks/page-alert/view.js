window.addEventListener('DOMContentLoaded',() => {
    const container = document.querySelector('article > .umw-block-content > .entry-content');
    if ( ! container ) {
        return;
    }

    const alert = document.querySelector( '.umw-page-alert');
    if ( ! alert ) {
        return;
    }

    container.prepend(alert);
});