let accountMatcher, pathMatcher;
setInterval(function () {
    if (window.location.href.match(/^https:\/\/twitter.com\/[^?]+\?.*$/)) {
        pathMatcher = window.location.href.match(/^https:\/\/twitter.com\/([^?]+)\?.*$/)[1];
        if (pathMatcher) {
            history.replaceState('','','/' + pathMatcher);
        }
        pathMatcher = null;
    }
    if (window.location.href.match(/^https:\/\/twitter.com\/([^/])+$/)) {
        accountMatcher = window.location.href.match(/^https:\/\/twitter.com\/([^/]+)$/)[1];
        if (accountMatcher) {
            history.replaceState('','','/' + accountMatcher.toLocaleLowerCase() + '/');
        }
        accountMatcher = null;
    }
}, 250);