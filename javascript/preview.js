function removepreview() {
    let preview = document.getElementById("clearpreview");
    let dummy = document.getElementById("previewdummy");
    let pcontent = document.getElementById("previewcontent");
    document.getElementById("previewcontent").innerHTML = "";
    if (preview.style.display != "none") {
        preview.style.display = "none";
    }
    if (dummy.style.display != "none") {
        dummy.style.display = "none";
    }
    if (pcontent.style.display != "none") {
        pcontent.style.display = "none";
    }
}

async function dopreview() {
    let preview = document.getElementById("clearpreview");
    let dummy = document.getElementById("previewdummy");
    let pcontent = document.getElementById("previewcontent");
    let content = document.getElementById("content").value;
    const resp = await fetch(previewendpoint, {
      method: "POST",
      body: "previewcontent=" + encodeURI(content),
      headers: {"Content-Type": "application/x-www-form-urlencoded"}
    });
    const formatted = await resp.text();
    let previewbox = document.getElementById("previewcontent");
    previewbox.innerHTML = formatted;
    if (preview.style.display == "none") {
        preview.style.display = "inline-block";
    }
    if (dummy.style.display == "none") {
        dummy.style.display = "block";
    }
    if (pcontent.style.display == "none") {
        pcontent.style.display = "block";
    }
}
