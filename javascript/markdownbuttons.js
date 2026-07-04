function format(type) {
    if (type == "bold") {
        var pattern = "**%s**";
    }
    else if (type == "italic") {
        var pattern = "*%s*";
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
    
    var textbox = document.getElementById("content");
    if (textbox.selectionStart || textbox.selectionStart == '0') {
        var startPos = textbox.selectionStart;
        var endPos = textbox.selectionEnd;
        textbox.value = textbox.value.substring(0, startPos)
            + pattern.replace("%s", textbox.value.substring(startPos, endPos))
            + textbox.value.substring(endPos, textbox.value.length);
    }
    else {
        textbox.value += pattern.replace("%s", "");
    }
}
