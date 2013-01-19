$(document).ready(function() {
	// Submit dummy form with CSRF token
	$(".quick_action").click(function(event) {
		event.preventDefault();

		// Set the URL for the dummy form and submit it.
		$("#dummy_form").attr("action", this.href);
		$("#dummy_form").submit();
	});

	// Focus stuff
	$(".focus_onload").first().focus();
});
