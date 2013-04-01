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

// init code
window.addEventListener("load", function() {
	var dropdown = document.forms[0].db_driver;

	toggleShit.call(dropdown);
	dropdown.addEventListener("change", toggleShit);
});
