var qdb = {
	strip_timestamps: function(i) {
		var txt = i.val();
		var ra = /^\d\d:\d\d(:\d\d)? /;
		var ra2 = /\n\d\d:\d\d(:\d\d)? /g;
		var re = /^\[\d\d:\d\d(:\d\d)?\] /;
		var re2 = /\n\[\d\d:\d\d(:\d\d)?\] /g;
		i.val(txt.replace(ra, '').replace(ra2, "\n").replace(re, '').replace(re2, "\n"));
	}
}