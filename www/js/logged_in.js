$(function(){
	$('#signout').on('click',function(){
		var title = 'Sign out';
		$.Dialog.confirm(title,'Are you sure you want to sign out?',function(sure){
			if (!sure) return;

			$.Dialog.wait(title,'Signing out');

			$.ajax({
				method: "POST",
				url: '/signout',
				success: function(data){
					if (typeof data !== 'object') return console.log(data) && $w.trigger('ajaxerror');

					if (data.status){
						$.Dialog.success(title,data.message);
						setTimeout(function(){
							window.location.reload();
						},1000);
					}
					else $.Dialog.fail(title,data.message);
				}
			});
		});
	});
});