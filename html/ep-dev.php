<?

require(__DIR__."/../class/serverClass-dev.php");

$server = new serverApi();

$json=file_get_contents("php://input");

header('Content-Type: application/json; charset=utf-8'); 
echo ($server->callApi($json));
exit(0);

?>