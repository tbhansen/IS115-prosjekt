<?php

session_start();

// Clear and end the user session
session_destroy();

// Redirect back to public homepage
header("Location: index.php");
exit;
