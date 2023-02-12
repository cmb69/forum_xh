/**
 * Copyright 2017-2021 Christoph M. Becker
 *
 * This file is part of Forum_XH.
 *
 * Forum_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Forum_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Forum_XH.  If not, see <http://www.gnu.org/licenses/>.
 */

var each = Array.prototype.forEach;

history.replaceState({forum_url: location.href}, document.title, location.href);

window.addEventListener("popstate", function (event) {
    if ("forum_url" in event.state) {
        var container = document.getElementsByClassName("forum_container")[0];
        retrieveWidget(container, event.state.forum_url, true);
    }
});

function retrieveWidget(container, url, isPop) {
    var request = new XMLHttpRequest;
    request.open("GET", url, true);
    request.onload = function() {
        if (this.status >= 200 && this.status < 300) {
            replaceWidget(container, this, isPop);
        }
    };
    request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    request.send();
    container.classList.add("forum_loading");
}

function replaceWidget(container, request, isPop) {
    container.outerHTML = request.response;
    ajaxify();
    initEditor();
    initDeleteForms();
    if (!isPop) {
        var url = request.responseURL;
        history.pushState({forum_url: url}, document.title, url);
    }
}

function ajaxify() {
    each.call(document.getElementsByClassName("forum_container"), function (container) {
        each.call(container.querySelectorAll("a:not(.forum_link)"), function (anchor) {
            anchor.onclick = (function () {
                retrieveWidget(container, this.href);
                return false;
            });
        });
    });
}
ajaxify();

function initEditor() {
    var form = document.querySelector("form.forum_comment");
    if (!form) {
        return;
    }
    var i18n = JSON.parse(form.getAttribute("data-i18n"));
    var textarea = form.elements.forum_text;

    function wrapIn(prefix, suffix) {
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var sel = textarea.value.substring(start, end);
        var carret = sel ? start + prefix.length + sel.length + suffix.length : start + prefix.length;
        textarea.value = textarea.value.substring(0, start) + prefix + sel + suffix +
                textarea.value.substring(end, textarea.textLength);
        textarea.setSelectionRange(carret, carret);
        textarea.focus();
    }

    function image() {
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var url = prompt(i18n.ENTER_URL, "http://");
        if (typeof url === "string") {
            var repl = "[img]" + url + "[/img]";
            var carret = start + repl.length;
            textarea.value = textarea.value.substring(0, start) + repl +
                    textarea.value.substring(end, textarea.textLength);
            textarea.setSelectionRange(carret, carret);
        }
        textarea.focus();
    }

    function iframe() {
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var url = prompt(i18n.ENTER_URL, "http://");
        if (typeof url === "string") {
            var repl = "[iframe]" + url + "[/iframe]";
            var carret = start + repl.length;
            textarea.value = textarea.value.substring(0,start) + repl + textarea.value.substring(end, textarea.textLength);
            textarea.setSelectionRange(carret, carret);
        }
        textarea.focus();
    }

    function url() {
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var url = prompt(i18n.ENTER_URL, "http://");
        if (typeof url === "string") {
            var sel = textarea.value.substring(start, end);
            var prefix = "[url=" + url + "]";
            var suffix = "[/url]";
            var carret = start + prefix.length + (sel ? sel.length + suffix.length : 0);
            textarea.value = textarea.value.substring(0, start) + prefix + sel + suffix +
                    textarea.value.substring(end, textarea.textLength);
            textarea.setSelectionRange(carret, carret);
        }
        textarea.focus();
    }

    function emoticon(tag) {
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var carret = start + tag.length;
        textarea.value = textarea.value.substring(0, start) + tag +
                textarea.value.substring(end, textarea.textLength);
        textarea.setSelectionRange(carret, carret);
        textarea.focus();
    }

    function preview() {
        var request = new XMLHttpRequest;
        request.open("GET", this.getAttribute("data-url") + "&forum_bbcode=" + encodeURIComponent(textarea.value), true);
        request.onload = (function () {
            if (this.status >= 200 && this.status < 300) {
                form.getElementsByClassName("forum_preview_container")[0].innerHTML = this.response;
            }
        });
        request.send();
    }

    var commands = {
        forum_bold_button: function () {
            wrapIn("[b]", "[/b]");
        },
        forum_italic_button: function () {
            wrapIn("[i]", "[/i]");
        },
        forum_underline_button: function () {
            wrapIn("[u]", "[/u]");
        },
        forum_strikethrough_button: function () {
            wrapIn("[s]", "[/s]");
        },
        forum_emoticon_button: function (event) {
            var div = form.getElementsByClassName("forum_emoticons")[0];
            document.addEventListener("click", function fn() {
                document.removeEventListener("click", fn);
                div.style.display = "none";
            });
            div.style.display = "block";
            event.stopPropagation();
        },
        forum_picture_button: image,
        forum_iframe_button: iframe,
        forum_link_button: url,
        forum_font_button: function (event) {
            var div = form.getElementsByClassName("forum_font_sizes")[0];
            document.addEventListener("click", function fn() {
                document.removeEventListener("click", fn);
                div.style.display = "none";
            });
            div.style.display = "block";
            event.stopPropagation();
        },
        forum_big_button: function () {
            wrapIn("[size=150]", "[/size]");
        },
        forum_small_button: function () {
            wrapIn("[size=67]", "[/size]");
        },
        forum_bulleted_list_button: function () {
            wrapIn("[list]\n", "\n[/list]");
        },
        forum_numeric_list_button: function () {
            wrapIn("[list=1]\n", "\n[/list]");
        },
        forum_list_item_button: function () {
            wrapIn("[*] ", "");
        },
        forum_quotes_button: function () {
            wrapIn("[quote]", "[/quote]");
        },
        forum_code_button: function () {
            wrapIn("[code]", "[/code]");
        },
        forum_preview_button: preview,
        forum_smile_button: function () {
            emoticon(":)");
        },
        forum_wink_button: function () {
            emoticon(";)");
        },
        forum_happy_button: function () {
            emoticon(":))");
        },
        forum_grin_button: function () {
            emoticon(":D");
        },
        forum_tongue_button: function () {
            emoticon(":P");
        },
        forum_surprised_button: function () {
            emoticon(":o");
        },
        forum_unhappy_button: function () {
            emoticon(":(");
        },
    };

    form.onsubmit = (function () {
        var title = document.getElementById('forum_title');
        if (title && title.value.length === 0) {
            alert(i18n.TITLE_MISSING);
            title.focus();
            return false;
        }
        if (textarea.textLength === 0) {
            alert(i18n.COMMENT_MISSING);
            textarea.focus();
            return false;
        }
        var container = document.getElementsByClassName("forum_container")[0];
        submitForm(form, container);
        return false;
    });

    var toolbar = document.getElementById("forum_toolbar");
    toolbar.outerHTML = toolbar.text;
    each.call(form.getElementsByTagName("button"), function (button) {
        if (button.className in commands) {
            button.onclick = commands[button.className];
        }
    });
}
initEditor();

function serialize(form) {
    var params = [];
    each.call(form.elements, function (element) {
        if (element.name) {
            params.push(encodeURIComponent(element.name) + "=" +
                        encodeURIComponent(element.value));
        }
    });
    return params.join("&");
}

function submitForm(form, container) {
    var request = new XMLHttpRequest;
    request.open("POST", form.action, true);
    request.onload = function () {
        if (this.status >= 200 && this.status < 300) {
            replaceWidget(container, this);
        }
    };
    request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
    request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    request.send(serialize(form));
    container.classList.add("forum_loading");
}

function initDeleteForms() {
    each.call(document.getElementsByClassName("forum_delete"), function (form) {
        form.onsubmit = (function () {
            var container = document.getElementsByClassName("forum_container")[0];
            if (confirm(this.getAttribute("data-message"))) {
                submitForm(form, container);
            }
            return false;
        });
    });
}
initDeleteForms();
