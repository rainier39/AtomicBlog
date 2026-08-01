function format(type) {
    var onnewline = false;
    var displace = false;
    var list = false;
    if (type == "bold") {
        var pattern = "**%s**";
    }
    else if (type == "italic") {
        var pattern = "*%s*";
    }
    else if (type == "code") {
        var pattern = "`%s`";
    }
    else if (type == "codeblock") {
        var pattern = "```%s```";
    }
    else if (type == "link") {
        var pattern = "[%s](https://example.com)";
    }
    else if (type == "image") {
        var pattern = "![alt text](%s)";
    }
    else if (type == "header") {
        var pattern = "# %s";
        onnewline = true;
    }
    else if (type == "hr") {
        var pattern = "---%s";
        onnewline = true;
        displace = true;
    }
    else if (type == "blockquote") {
        var pattern = "> %s";
        onnewline = true;
    }
    else if (type == "list") {
        var pattern = "* ";
        onnewline = true;
        list = true;
    }
    
    var textbox = document.getElementById("content");
    if (textbox.selectionStart || textbox.selectionStart == '0') {
        var startPos = textbox.selectionStart;
        var endPos = textbox.selectionEnd;
        if (onnewline) {
            if ((startPos != 0) && (textbox.value[startPos-1] != "\n")) {
                pattern = "\n" + pattern;
            }
        }
        if (displace) {
            if (startPos < endPos) {
                pattern = pattern.replace("%s", "\n%s");
            }
        }
        if (list) {
            textbox.value = textbox.value.substring(0, startPos)
                + pattern
                + textbox.value.substring(startPos, endPos).replaceAll("\n", "\n" + pattern)
                + textbox.value.substring(endPos, textbox.value.length);
        }
        else {
            textbox.value = textbox.value.substring(0, startPos)
                + pattern.replace("%s", textbox.value.substring(startPos, endPos))
                + textbox.value.substring(endPos, textbox.value.length);
        }
    }
    else {
        textbox.value += pattern.replace("%s", "");
    }
}
