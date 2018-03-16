function refreshPage() {
	setTimeout("location.reload();", 1000);
}

function verifyRemoval(charName, charID)
{
	if (confirm('Are you sure you want to remove ' + charName + '?')) {
		$.ajax({
		  type: "POST",
 		  url: '/manage/',
		  data: {'remove' : charID},
		  success: function(data) { window.location = '/manage/'; }
		});
	}
}
