jQuery(document).ready(function ($) {
	$('#ttd-settings-form #submit').click(function (e) {
		if( !confirm('Are you sure that you want to continue?') )
			e.preventDefault();
	});
});
