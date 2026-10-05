async function dopreview() {
    let preview = document.getElementById("preview");
    let content = document.getElementById("content").value;
    const resp = await fetch(previewendpoint, {
      method: "POST",
      body: "previewcontent=" + encodeURI(content),
      headers: {"Content-Type": "application/x-www-form-urlencoded"}
    });
    const formatted = await resp.text();
    let previewbox = document.getElementById("previewcontent");
    if (previewbox == null) {
        preview.outerHTML += "<br><div class='postContent' id='previewcontent'>" + formatted + "</div>";
    }
    else {
        previewbox.innerHTML = formatted;
    }
}
