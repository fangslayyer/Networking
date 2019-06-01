<?php
require 'config.php';

if( 	setcookie('includeOrder_column','', 1) 
	&&	setcookie('includeOrder_result','', 1)) {
	echo "Cookies cleared";
}