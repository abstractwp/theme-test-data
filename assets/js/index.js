jQuery(document).ready(function ($) {
	$("#ttd-settings-form #submit").click(function (e) {
		e.preventDefault();
		$(this).attr("disabled", "disabled");
		if (!confirm("Are you sure that you want to continue?")) {} else {
			var action = $(
				"input[name='ttd-options[ttd_import_demo]']:checked"
			).val();
			$("#import-results").show();
			$.ajax({
					url: WPURLS.siteurl + "/wp-json/ttd/v1/" + action,
					dataType: "html",
				})
				.done(function (html) {
					$("#import-results").html(html);
					setTimeout(() => {
						location.reload();
					}, 3000);
				})
				.fail(function (jqXHR, textStatus) {
					alert("Request failed: " + textStatus);
				});
		}
	});
});
