<?php
include "lib/SabreMW.php";

$smw = new SabreMW(Null);

if ($smw->test()) 
{ 
	echo "OK";
}
?>
