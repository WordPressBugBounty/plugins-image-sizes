(function () {
    'use strict';

    var observer;

    function loadImage(img) {
        var src = img.getAttribute('data-src');
        var srcset = img.getAttribute('data-srcset');

        if (src) {
            img.src = src;
            img.removeAttribute('data-src');
        }

        if (srcset) {
            img.srcset = srcset;
            img.removeAttribute('data-srcset');
        }

        img.classList.remove('thumbpress-lazy');
    }

    if ('IntersectionObserver' in window) {
        observer = new IntersectionObserver(function (entries) {
            for (var i = 0; i < entries.length; i++) {
                if (entries[i].isIntersecting) {
                    loadImage(entries[i].target);
                    observer.unobserve(entries[i].target);
                }
            }
        }, {
            rootMargin: '200px 0px'
        });

        // Observe all lazy images.
        var images = document.querySelectorAll('img.thumbpress-lazy');
        for (var i = 0; i < images.length; i++) {
            observer.observe(images[i]);
        }

        // Also observe dynamically added images (AJAX, infinite scroll, etc).
        if ('MutationObserver' in window) {
            var mutationObserver = new MutationObserver(function (mutations) {
                for (var m = 0; m < mutations.length; m++) {
                    var nodes = mutations[m].addedNodes;
                    for (var n = 0; n < nodes.length; n++) {
                        if (nodes[n].nodeType === 1) {
                            if (nodes[n].tagName === 'IMG' && nodes[n].classList.contains('thumbpress-lazy')) {
                                observer.observe(nodes[n]);
                            }
                            var childImages = nodes[n].querySelectorAll && nodes[n].querySelectorAll('img.thumbpress-lazy');
                            if (childImages) {
                                for (var c = 0; c < childImages.length; c++) {
                                    observer.observe(childImages[c]);
                                }
                            }
                        }
                    }
                }
            });

            mutationObserver.observe(document.body, { childList: true, subtree: true });
        }
    } else {
        // Fallback: load all images immediately for old browsers.
        var images = document.querySelectorAll('img.thumbpress-lazy');
        for (var i = 0; i < images.length; i++) {
            loadImage(images[i]);
        }
    }
})();
