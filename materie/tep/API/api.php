<?php

function documentazione(){
	header("Content-Type: text/html; charset= utf-8");
    ?>
    <!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">

    <title>Api games service</title>
  </head>
  <body>
    <div class="container"> 
	    <h1 class="alert alert-info">API game service</h1>
	    <h2 class="alert alert-danger" title="Rivolgiti al prof. Grena" alt="Rivolgiti al prof. Grena">BASIC AUTH REQUIRED</h2>
        
       	
        <h3 class="alert alert-success">Create new game</h3>
        <div class="jumbotron">
            method : POST <br/>
            endpoint : https://classe5ID.altervista.org/games/partita/{Username} <br/>
            returned data: game's ID 
        </div>

        <h3 class="alert alert-success">Join to new game</h3>
        <div class="jumbotron">
            method : POST <br/>
            endpoint : https://classe5ID.altervista.org/games/join/{game's ID}/{Username} <br/>
            returned data: game's ID 
        </div>

        <h3 class="alert alert-success">Insert your move</h3>
        <div class="jumbotron">
            method : POST <br/>
            endpoint : https://classe5ID.altervista.org/games/mossa/{game's ID}/{Nome utente}/{move} <br/>
            returned data: move's ID 
        </div>

        <h3 class="alert alert-success">Avaiabled games</h3>
        <div class="jumbotron">
            method : GET <br/>
            endpoint : https://classe5ID.altervista.org/games/partita <br/>
            returned data: game's LIST 
        </div>
        
        <h3 class="alert alert-success">Avaiabled games - N player</h3>
        <div class="jumbotron">
            method : GET <br/>
            endpoint : https://classe5ID.altervista.org/games/partitaN/{n} <br/>
            returned data: game's LIST  
        </div>
		
        <h3 class="alert alert-success">Last move</h3>
        <div class="jumbotron">
            method : GET <br/>
            endpoint : https://classe5ID.altervista.org/games/mossa/{game's ID} <br/>
            returned data: last move 
        </div>

		<h3 class="alert alert-success">Game's Moves</h3>
        <div class="jumbotron">
            method : GET <br/>
            endpoint : https://classe5ID.altervista.org/games/mosse/{game's ID} <br/>
            returned data: moves's LIST 
        </div>
		
        

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
  </body>
</html>
	
<?php
}

function db_connect(){
	$dsn = "mysql:host=localhost;dbname=my_classe5id;charset=utf8mb4";
	$db = new PDO($dsn, "classe5id", "password", [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES => false,
	]);
    return $db;
}

function insert_partita($db,$player){
if (strpos(strtoupper($player), "GRANA")!==false)
    return -1;
  $stmt = $db->prepare("INSERT INTO games_partite(PLAYER1) VALUES (?)");
  $stmt->execute([$player]);
  return (int) $db->lastInsertId();
}

function insert_mossa($db,$partita,$player,$mossa){
	
  $stmt = $db->prepare("SELECT * from games_partite WHERE ID=?");
  $stmt->execute([$partita]);
  $row = $stmt->fetch();
  if ($row===false)
  return -1;
  if ( $row["PLAYER2"]==null)
  return -2;
  if ( $player==null)
  return -2;

  if ($row["PLAYER1"]!=$player && $row["PLAYER2"]!=$player && $row["PLAYER3"]!=$player && $row["PLAYER3"]!=$player &&
  $row["PLAYER4"]!=$player && $row["PLAYER5"]!=$player && $row["PLAYER6"]!=$player && $row["PLAYER7"]!=$player)
  return -3;
  
  $stmt = $db->prepare("INSERT INTO games_mosse(MOSSA,PLAYER,GAME) VALUES (?,?,?)");
  $stmt->execute([$mossa,$player,$partita]);
  return (int) $db->lastInsertId();
}

function mosse($db,$partita){
  $stmt = $db->prepare("SELECT * FROM games_partite WHERE ID=?");
	$stmt->execute([$partita]);
  if (!$stmt->fetch())
  	return -1;
    
  $stmt = $db->prepare("SELECT * from games_mosse WHERE GAME=? ORDER BY ID DESC");
	$stmt->execute([$partita]);
  $ris=array();
  while($row = $stmt->fetch()) $ris[]=$row;
  return $ris;
}


function last_mossa($db,$partita){
  $stmt = $db->prepare("SELECT * FROM games_partite WHERE ID=?");
	$stmt->execute([$partita]);
  if (!$stmt->fetch())
  	return -1;
    
  $stmt = $db->prepare("SELECT * from games_mosse WHERE GAME=? ORDER BY ID DESC LIMIT 1");
  $stmt->execute([$partita]);
  $row = $stmt->fetch();
  return $row;
}

function avaiable_partite($db){
  $db->exec("delete from games_partite WHERE PLAYER2 IS NULL AND CREATED_AT < NOW() - INTERVAL 1 DAY	");
  
  $result = $db->query("SELECT * from games_partite WHERE PLAYER2 IS NULL AND CREATED_AT >= NOW() - INTERVAL 1 DAY ORDER BY ID DESC");
  $risp=array();
  while($row = $result->fetch())
    $risp[]=$row;
  return $risp;
}
function avaiable_partiteN($db,$n){
	$n = (int) $n;
	if ($n < 2 || $n > 7) {
		return [];
	}

  $db->exec("delete from games_partite WHERE PLAYER2 IS NULL AND CREATED_AT < NOW() - INTERVAL 1 DAY	");
  
  $result = $db->query("SELECT * from games_partite WHERE PLAYER$n IS NULL AND CREATED_AT >= NOW() - INTERVAL 1 DAY ORDER BY ID DESC");
  $risp=array();
  while($row = $result->fetch())
    $risp[]=$row;
  return $risp;
}


function join_partita($db,$partita,$player){
if (strpos(strtoupper($player), "GRANA")!==false)
    return -2;

  $partite=avaiable_partiteN($db,7);
  $find=false;
  foreach ($partite as $p)
    if ($p["ID"]==$partita){ 
    $find=true;
    $n=-1;
    for ($i=1;$i<=7;$i++){
    	$giocatore="PLAYER".$i;
    	if ($p[$giocatore]==$player)
      		return -1;
    	
        if ($p[$giocatore]==null && $n==-1)
        	$n=$i;
        }
    }
  if (!$find) return 0;
  $stmt = $db->prepare("UPDATE games_partite SET PLAYER$n=? WHERE ID=?");
  if ($stmt->execute([$player,$partita])) return 1;
  else return -2;
}


function gestisci_URI(){
	$richiesta= $_SERVER["REQUEST_URI"];
	$param=explode("/",$richiesta);
	if (count($param)>2)
      return array_slice($param,2);
    return null;
}

function errore($tipo,$error){
	http_response_code($tipo);
    $ris='{"error":'.$tipo.';"description":"'.$error.'"}';
    die( $ris);
}

function rispondi_json($data){
	http_response_code(200);
	echo json_encode($data,JSON_INVALID_UTF8_IGNORE);
}

class risposta{
    public $method;
    public $action;
    public $data;

    public function __construct($m,$a,$d) {
        $this->method = $m;
        $this->action = $a;
        $this->data = $d;
    }

}
// Allow from any origin
if(isset($_SERVER["HTTP_ORIGIN"]))
{
    // You can decide if the origin in $_SERVER['HTTP_ORIGIN'] is something you want to allow, or as we do here, just allow all
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
}
else
{
    //No HTTP_ORIGIN set, so we allow any. You can disallow if needed here
    header("Access-Control-Allow-Origin: *");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 600");    // cache for 10 minutes

if($_SERVER["REQUEST_METHOD"] == "OPTIONS")
{
    if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_METHOD"]))
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT"); //Make sure you remove those you do not want to support

    if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"]))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    //Just exit with 200 OK with the above headers for OPTIONS method
    exit(0);
}
//From here, handle the request as it is ok
   
//header("Access-Control-Allow-Credentials: true");
//header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, Authorization, Accept, Accept-Encoding");
//header("Access-Control-Allow-Methods: GET,POST, OPTIONS");
header("Content-Type: application/json; charset= utf-8");
$oggetto=null;

if (gestisci_URI()[0]=="")
       die (documentazione());

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Grena 4ID"');
    header('HTTP/1.0 401 Unauthorized');
    errore(401,"Basic Auth not sent");
    exit;
} 
else {
    if ($_SERVER['PHP_AUTH_USER']!="4IE" && $_SERVER['PHP_AUTH_USER']!="4ID" 
    || $_SERVER['PHP_AUTH_PW']!="Falzone" && $_SERVER['PHP_AUTH_PW']!="Mazzoleni")
    	errore(401,"Basic Auth error. Auth_user or password not valid");
    else{
		$parametri=gestisci_URI();
        if (is_null($parametri))
          documentazione();
        
        //rispondi_json($parametri);
        //die;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        	if (!isset($parametri[0]))
              errore(400,"partita or mossa expexted");
            if ($parametri[0]=="partita"){
            	if (!isset($parametri[1]))
                  errore(400,"player expected");
                else { 
            	//inserisci partita
                $partita["id"]=insert_partita(db_connect(),$parametri[1]);
                 switch($partita["id"]){
					case -1: 	errore(400,"Player name error");
                 }
				$oggetto=new risposta("POST","New match created",$partita);
                }
            }
            else if ($parametri[0]=="mossa"){
				//inserisci mossa
                if (!isset($parametri[1]))
                  errore(400,"ID partita expected");
                else if(!isset($parametri[2]))
                  errore(400,"player expected");
                else if (!isset($parametri[3]))
                  errore(400,"mossa expected");
                else{
                  $mossa=array();
                  $mossa["id_partita"]=$parametri[1];
                  $mossa["player"]=$parametri[2];
                  $mossa["mossa"]=$parametri[3];
                  $id=insert_mossa(db_connect(),$mossa["id_partita"],$mossa["player"],$mossa["mossa"]);
                  switch($id){
					case -2: 	errore(400,"Match is waiting for player join");
					case -3: 	errore(400,"Player name error");
					case -1: 	errore(400,"Match is not avaiabled");
					default:  $mossa["id"]=$id;$oggetto=new risposta("POST","Insert your play",$mossa);
                  }
                }
                  
            }
            else if ($parametri[0]=="join"){
			    if (!isset($parametri[1]))
                  errore(400,"ID partita expected");
                else if(!isset($parametri[2]))
                  errore(400,"player expected");
                else{
                	$partita=array();
                    $partita["id"]=$parametri[1];
                    $partita["player"]=$parametri[2];
                    $ris=join_partita(db_connect(),$partita["id"],$partita["player"]);
                    if ($ris==1)
                		$oggetto=new risposta("POST","connected to the match",$partita);
                    else if ($ris==0)
                    	errore(400,"Match not avaiable");
                    else if ($ris==-1)
                        errore(400,"Player name already used");
                     else if ($ris==-2)
                        errore(400,"Player name error");
                    else errore(500,"DB error");
            }
            
        }
          else
            	errore(400,"Request error");          

        }
        else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        	if (!isset($parametri[0]))
              errore(400,"partita or mossa expexted");
            if ($parametri[0]=="partita"){
            	$partita=array();
            	$partita=avaiable_partite(db_connect());
                //for ($i=0;$i<10;$i++) $partita[]=rand(1,100);
            	//elenco partite disponibili
				$oggetto=new risposta("GET","matches avaiable",$partita);
			}
            else if ($parametri[0]=="partitaN"){
            	if (!isset($parametri[1]))
                  errore(400,"N players expected");
                $n=$parametri[1];
            	$partita=array();
            	$partita=avaiable_partiteN(db_connect(),$n);
                //for ($i=0;$i<10;$i++) $partita[]=rand(1,100);
            	//elenco partite disponibili
				$oggetto=new risposta("GET","matches avaiable",$partita);
			}
            else if ($parametri[0]=="mossa"){
				//inserisci mossa
                if (!isset($parametri[1]))
                  errore(400,"ID partita expected");
                else{
                $mossa["id_partita"]=$parametri[1];
                $mossa["play"]=last_mossa(db_connect(),$mossa["id_partita"]);
                 if ($mossa["play"]===-1)
                	errore(404,"Match unavailable");
                $oggetto=new risposta("GET","Last move",$mossa);
                }
             }
            else if ($parametri[0]=="mosse"){
				//inserisci mossa
                if (!isset($parametri[1]))
                  errore(400,"ID partita expected");
                else{
                $mossa["id_partita"]=$parametri[1];
                $mossa["moves"]=mosse(db_connect(),$mossa["id_partita"]);
                if ($mossa["moves"]===-1)
                	errore(404,"Match unavailable");
                $oggetto=new risposta("GET","All moves",$mossa);
                }
             }
            else
            	errore(400,"Request error");
            //restituisci mossa
        }
        else
            errore(405,"Method not allowed");

		array_push($parametri,$oggetto);
        rispondi_json($oggetto);

	}
}