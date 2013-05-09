<html>
<head>
	<title>This is a title</title>
</head>
<body>

<p>My name is {$name -escape}.</p>


<p>I live in {$user.city}</p>

{$user.}
<label>City: <input type="text" value="{$city}"></label>
{/$user.}

<?php echo 'hi'; ?>

<table>
	<thead>
	<tr>
		<th>header</th>
	</tr>
	</thead>
	<tbody>
	{$rows}
	<tr>
		<td>
			{$cell}
		</td>
	</tr>
	{/$rows}
	</tbody>
</table>

{? $user.age > 19 }
<p>You are no longer a teenager.</p>
{?:}
<p>You are under 20.</p>
{/?}

<p>Gender:
{? $user.gender == "male" }
Male
{?: $user.gender == "female"}
Female
{?:}
Unknown
{/?}</p>

<ul>
	{$values}
	<li>{$_}</li>
	{/$values}
</ul>


</body>
</html>