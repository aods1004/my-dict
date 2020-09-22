let match;
setInterval(function () {
    console.log("check url...");
    if (window.location.href.match(/^https?:\/\/[^?]+\?.*$/)) {
        match = window.location.href.match(/^(https:\/\/[^?]+)\?.*$/)[1];
        history.replaceState('','', match);
    }
}, 500);
