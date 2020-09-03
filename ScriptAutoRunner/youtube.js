if (window.location.href.match(/^https:\/\/www\.youtube\.com\/watch\?v=/)) {
    if (window.location.href.length > 43) {
        setCanonicalLinkTag(window.location.href.substr(0, 43));
    }
}

const observer = new MutationObserver((mutations) => {
    mutations.forEach(() => {
        if (window.location.href.match(/^https:\/\/www\.youtube\.com\/watch\?v=/)) {
            if (window.location.href.length > 43) {
                let url = window.location.href.substr(0, 43);
                setCanonicalLinkTag(url);
            }
        }
    });
});

observer.observe(
    document.getElementsByTagName('body')[0],
    {attributes: true, childList: true, characterData: true, subtree: true});

function setCanonicalLinkTag(url) {
    let links = document.getElementsByTagName("link");
    for (let i = 0; i < links.length; i++) {
        if (links[i].rel === "canonical") {
            links[i].rel = "canonical-back";
        }
    }
    console.log("canonical uri を「" + url + "」に変更しました");
    let canonicalLinkTag = window.document.createElement('link');
    canonicalLinkTag.rel = "canonical";
    canonicalLinkTag.href = url;
    window.document.head.append(canonicalLinkTag);
}

