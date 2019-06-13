<?php
$file = $_FILES["fileToUpload"];
$filename = $file["name"];

$host = "macderakhadb.database.windows.net";
$user = "rakha";
$pass = "Jamblang2019";
$db = "macde";
// try {
//     $conn = new PDO("sqlsrv:server = $host; Database = $db", $user, $pass);
//     $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// } catch (Exception $e) {
//     echo "Failed: " . $e;
// }
