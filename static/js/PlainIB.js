/*
 * PlainIB.js - Copyright (c) 2013 Plainboards.org
 *
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://www.wtfpl.net/ for more details.
 */

//
// Style crap
//

if (typeof changeStyle !== "function") {
	window.changeStyle = function (name) {
		var link = $("#sitestyle");
		link.attr("href", lessHandler + "?file=" + styles[name]);
	};
}

function createStyleSwitcher() {
	if (typeof styles !== "object")
		return null;

	// Get selected/defaulted stylesheet
	var cookie = new Cookie();
	var selected = cookie.get("style");

	if (!selected)
		selected = $("#sitestyle").attr("title");

	// Create <select> for switcher
	var switcher = $(document.createElement("select"));

	// Counter for styles
	var count = 0;

	for (style in styles) {
		count++;

		var option = $(document.createElement("option"));

		// The text automatically becomes the value
		$(option).text(style);

		// If this is the current style, make it selected
		if (style === selected)
			$(option).attr("selected", "selected");

		switcher.append(option);
	}

	// no styles to switch between
	if (count < 2)
		return null;

	// set onchange event for the switcher
	switcher.change(function() {
		var value = $(this).val();
		changeStyle(value);

		// Save the new style
		var cookie = new Cookie();
		cookie.set("style", value);
	});

	return switcher;
}


//
// Callbacks
//

function runCallbacks() {
	var callbacks = $(document.body).data("callback");

	if (!callbacks)
		return;

	callbacks = callbacks.split(/ +/);

	for (var i = 0, j = callbacks.length; i < j; i++)
		if (window[callbacks[i]])
			window[callbacks[i]]();
}

function doStyleSwitchers() {
	var ss = createStyleSwitcher();

	if (!ss) {
		// no styles to switch between
		return;
	}

	$(".ss-list").html(ss);
	$(".ss-unhide").removeClass("noscreen");
}

function doConfig() {
	// use bootstrap's tooltips
	$(".configform label[title]").tooltip({
		placement: "top",
		delay: { show: 0, hide: 0 }
	});

	// toggle default value
	$(".toggle-reset").change(function() {
		var key = "config_" + this.name.match(/\[(.*)\]/)[1];
		var inputField = $("#" + key);

		// enable/disable the input field as appropriate
		inputField.prop("disabled", this.checked);

		// handle boolean options
		if (inputField.attr("type") == "checkbox") {
			inputField.prop("checked", !inputField.prop("checked"));
			return;
		}

		// set the SQL-stored input so we can retrieve it
		// gets run the first time a checkbox is ticked
		if (this.checked && !$(this).data("has-sql-stored")) {
			$(this).data("sql-stored", inputField.attr("value"));

			// fucking weak typing...
			$(this).data("has-sql-stored", true);
		}

		// we're ticking the checkbox
		var dataSource = this.checked ? "default" : "sql-stored";

		inputField.attr("value", $(this).data(dataSource));
	});
}


//
// Global init
//

$(document).ready(function() {
	// run page-specific callbacks
	runCallbacks();

	// Create style switchers
	doStyleSwitchers();

	// Focus stuff
	$(".focus_onload").first().focus();

	// For W3C compliancy, since size="" isn't allowed on file inputs
	$("input[data-size]").attr("size", function() {
		return $(this).data("size");
	});
});

// Submit dummy form with CSRF token
$(".quick_action").click(function(event) {
	event.preventDefault();

	// Set the URL for the dummy form and submit it.
	$("#dummy_form").attr("action", this.href);
	$("#dummy_form").submit();
});
