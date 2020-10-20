if (window.location.href.match(/^https?:\/\/b.hatena.ne.jp\/entry\/.*/)) {
    const postEditTitle = () => {
        let classAttr = document.querySelector(".entry-edit").getAttribute("class");
        console.log(classAttr);
        if (classAttr.match(/is-permitted/)) {
            let title = decodeURIComponent(window.location.hash).substr(1);
            let current = document.querySelector(".js-entry-edit-modal-title > input").value;
            if (title !== "done") {
                window.location.hash = "done";
                if (current !== title) {
                    document.querySelector(".js-entry-edit-modal-title > input").value = title;
                }
                document.querySelector(".entry-editModal-btnArea > button.entry-editModal-decide.js-entry-edit-modal-decision-button").click();
            } else {
                if (current.match(/^https:\/\/www\.youtube\.com\/watch/)) {
                    setTimeout(function () {
                        window.location.reload();
                    }, 1000);
                } else {
                    setTimeout(function () {
                        window.location.hash = "should_close";
                        window.close();
                    }, 1000);
                }
            }
        }
    };
    const observer = new MutationObserver((mutations) => {
        mutations.forEach(postEditTitle);
    });
    observer.observe(
        document.querySelector(".entry-edit"),
        {attributes: true, childList: true, characterData: true, subtree: true});

    postEditTitle();
    if (window.location.hash.substr(1) === 'done') {
        setInterval(postEditTitle, 1000);
    }
}

