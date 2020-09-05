let currentUrl = "";
if (window.location.href.match(/^https:\/\/www\.youtube\.com\/watch\?v=/)) {
    setCanonicalLinkTag(window.location.href.substr(0, 43));
} else {
    setCanonicalLinkTag(window.location.href);
}
const observer = new MutationObserver((mutations) => {
    mutations.forEach(() => {
        if (window.location.href.match(/^https:\/\/www\.youtube\.com\/watch\?v=/)) {
            setCanonicalLinkTag(window.location.href.substr(0, 43));
        } else {
            setCanonicalLinkTag(window.location.href);
        }
    });
});

observer.observe(
    document.getElementsByTagName('body')[0],
    {attributes: true, childList: true, characterData: true, subtree: true});

function setCanonicalLinkTag(url) {
    if (currentUrl !== url) {
        let links = document.getElementsByTagName("link");
        for (let i = 0; i < links.length; i++) {
            if (links[i].rel === "canonical") {
                links[i].remove();
            }
        }
        console.log("canonical uri を「" + url + "」に変更しました");
        let canonicalLinkTag = window.document.createElement('link');
        canonicalLinkTag.rel = "canonical";
        canonicalLinkTag.href = url;
        window.document.head.append(canonicalLinkTag);
        currentUrl = url;
    }
}

