String.prototype.hasClass = function(target) {
	return new RegExp("(?:^| )" + target + "(?:$| )").test(this);
}

function addClass(el, target) {
	el.className += " " + target;
}

function delClass(el, target) {
	var regex = new RegExp("(?:^| +)" + target + "(?:$| +)");
	el.className = el.className.replace(regex, " ");
}

function toggleShit() {
	var driver = this.options[this.selectedIndex].value;
	var elements = document.getElementsByClassName("driver-specific");

	for (var i = 0, j = elements.length; i < j; i++) {
		if (elements[i].className.hasClass(driver)) {
			delClass(elements[i], "auto-hidden");
		} else {
			addClass(elements[i], "auto-hidden");
		}
	}
}

/* start page callback */
function start_cb() {
	var dropdown = document.forms[0].db_driver;

	toggleShit.call(dropdown);
	dropdown.addEventListener("change", toggleShit);
}

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
