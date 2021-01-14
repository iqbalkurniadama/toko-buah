<?php 
$konek = mysqli_connect("localhost","root","") or die (mysql_error());
$conn = mysqli_select_db($konek,"dbstbi-data") or die (mysql_error());
