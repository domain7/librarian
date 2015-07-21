<?php

	// Some basic utility functions
	require_once "includes/utility.php";

	// Load, instantiate Librarian class
	require_once "includes/class.librarian.php";
	$Librarian = New Librarian();

	// Build the library
	$Librarian->make_library();

	// Bootstrap the downloader too. Downloads submit to self.
	$Librarian->init_downloader();

	// Bootstrap the raw viewer for markup
	$Librarian->view_raw();

	// Load the theme functions
	require_once "theme/functions.php";

	// Load the theme template file
	require_once "theme/index.php";
