function refreshPage() {
	setTimeout("location.reload();", 1000);
}

function verifyRemoval(charName, charID)
{
	if (confirm('Are you sure you want to remove ' + encodeURI(charName) + '?')) {
		$.ajax({
		  type: "POST",
 		  url: '/manage/',
		  data: {'remove' : charID},
		  success: function(data) { window.location = '/manage/'; }
		});
	}
}

function stc()
{
return;
	var stc = new Audio('/audio/SkillTrained.mp3');
	stc.volume = 0.1;
	stc.play();
}
