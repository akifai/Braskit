/*
 * config page callback
 * replaces hostnames in the copy/paste URLs, just in case
 */
function config_cb() {
	var urls = document.getElementsByClassName("url");
	var url_re = /^(https?:\/\/)(.*?\/)/;

	var new_host = window.location.href.match(url_re)[2];

	for (var i = 0, j = urls.length; i < j; i++)
		urls[i].innerHTML = urls[i].innerHTML.replace(url_re, "$1" + new_host);
}

window.addEventListener("load", function() {
	var callback = document.body.getAttribute("data-callback");

	if (callback)
		window[callback]();
});
