<html>
<head>
	<title>Accept Header Test</title>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
</head>
<body>
<p>This demonstrates Microsite's ability to process accept headers to differentiate routes.</p>
<ul>
	<li>
		<a class="html" href="/accept">Click this to get HTML</a>
	</li>
	<li>
		<a class="json" href="/accept">Click this to get JSON</a>
	</li>
</ul>
<div id="result"><?= $message ?></div>
<script>
	$(function(){
		$('.json').click(function(ev){
			$.getJSON($(this).attr('href'), function(data){
				$('#result').html(data.message);
			});
			ev.preventDefault();
		});
		$('.html').click(function(ev){
			$('#result').load($(this).attr('href') + ' #result');
			ev.preventDefault();
		});
	});
</script>
</body>
</html>