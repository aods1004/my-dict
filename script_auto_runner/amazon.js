
adjustCanonicalLinkTag();

const observer = new MutationObserver((mutations) => {
    mutations.forEach(() => {
        adjustCanonicalLinkTag();
    });
});

observer.observe(
    document.getElementsByTagName('head')[0],
    {attributes: true, childList: true, characterData: true, subtree: true});

function adjustCanonicalLinkTag() {
    let links = document.getElementsByTagName("link");
    let matches, url, path;
    for (let i = 0; i < links.length; i++) {
        if (links[i].rel === "canonical") {
            matches = links[i].href.match(/\/dp\/(\w+)/);
            if (matches[1]) {
                path = "/gp/product/" + matches[1];
                url = "https://www.amazon.co.jp" + path;
                if (url !== window.location.href) {
                    history.replaceState('','',path);
                }
                links[i].remove();
                let canonicalLinkTag = window.document.createElement('link');
                canonicalLinkTag.rel = "canonical";
                canonicalLinkTag.href = url;
                window.document.head.append(canonicalLinkTag);
                console.log("canonical uri を「" + url + "」に変更しました");
            }
        }
    }
}
