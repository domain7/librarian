<!doctype html>

<html lang="en">
<head>
	<meta charset="utf-8">

	<title><?php echo $Librarian->title; ?></title>
	<meta name="description" content="<?php echo $Librarian->description; ?>">

	<link rel="shortcut icon" href="<?php echo $Librarian->template_path; ?>/favicon.ico" />

	<?php $Librarian->the_stylesheet(); ?>

	<!--[if lt IE 9]>
	<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

</head>

<body>

	<div class="menubar">
		<div class="container">
			<h1 class="menubar-title"><?php echo $Librarian->title; ?></h1>
		</div>
	</div>

	<div class="container l-main">

		<?php $Librarian->the_navigation();?>
		<?php $Librarian->the_library(); ?>

	</div>

	<?php $Librarian->the_scripts(); ?>

</body>
</html>
