<html>
	<head>
		<title>Microsite Debug Page</title>
	</head>

	<body>
		<h1>Microsite Debug Page</h1>

		<h2>Rendered Output</h2>
		<?php echo $output; ?>

		<h2>View Variables</h2>
		<pre><?php echo htmlspecialchars(print_r($_response->get_vars(), 1)); ?></pre>

		<h2>View Properties</h2>
		<pre><?php echo htmlspecialchars(print_r($_response->get_props(), 1)); ?></pre>

	</body>
</html>