var styles = {{ style.getJSON()|raw }};

function changeStyle(name) {
    var linkEl = document.getElementById("sitestyle");

    if (linkEl) {
        linkEl.href = styles[name];
    }
}

(function () {
    var match = document.cookie.match(/(?:^|;\s+)style=(.*?)(?:;|$)/);

    if (!match || match.length < 2) {
        return;
    }

    changeStyle(unescape(match[1]));
})();
