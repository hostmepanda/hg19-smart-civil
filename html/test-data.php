<?

require(__DIR__."/../class/serverClass-dev.php");

$server = new serverApi();
print_r($_GET);
$json=file_get_contents("php://input");

header('Content-Type: application/json; charset=utf-8'); 
print_r( 
    json_decode(
        $server->callApi(
            json_encode( 
                [
                    "method"=>$_GET["method"],
                    "payload"=>["uid"=>"2"]
                ] 
            )
        )
    ) 
    ,false);
exit(0);

?>