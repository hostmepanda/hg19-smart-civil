<?

class serverApi{ 

    var $db;
    var $method;
    var $request;
    var $status_resp400;
    var $status_resp200;

    public function __construct() {

        $this->db=new Redis();
        $this->db->pconnect('127.0.0.1', 6379);
        $this->status_resp400=[
            "status"=>"400",
            "text"=>"Bad request",
            "called"=>"",
        ];
        $this->status_resp200=[
            "status"=>"200",
            "text"=>"OK",
        ];
        $this->method=[
            "getUser"=>"getUser",
            "getEvent"=>"getEvent",
            "getEvents"=>"getEvents",
            "createEvent"=>"createEvent",
            "createUser"=>"createUser",
            "participate"=>"participate",
            "comment"=>"comment",
            "startParticipate"=>"startParticipate",
            "getCategories"=>"getCategories",
            "getCategory"=>"getCategory",
            "createCategory"=>"createCategory",
            "startEvent"=>"startEvent",
            "finishEvent"=>"finishEvent",
            "addLikes"=>"addLikes",
            "addDislikes"=>"addDislikes",
            "test"=>"test",
            "test_getUser"=>"test",
            "test_getEvent"=>"test",
            "test_getEvents"=>"test",
            "test_createEvent"=>"test",
            "test_participate"=>"test",
            "test_comment"=>"test",
            "test_startParticipate"=>"test",
        ];

    }

    public function callApi($input){
        $this->request=json_decode($input,false);
        // print_r($this->request);
        $this->status_resp400["called"]=$this->request->method;
        if(!isset($this->request->method) || !isset($this->request->payload)){
            return json_encode($this->status_resp400);
        }
        if(isset($this->method[$this->request->method])){
            $callFunction=$this->method[$this->request->method];
            
            return json_encode($this->$callFunction());
            // return json_encode($this->status_resp200);
        }

        return json_encode($this->status_resp400);
    }

    public function getEvent(){
        if(!isset($this->request->payload->eid)){
            $this->request->payload->eid=$this->db->get("eventId");
        }

        $eventRange=$this->db->lrange("events", 0, -1);
        foreach($eventRange as $arrayIndex => $eventRaw){
            $eventJSON=json_decode($eventRaw,false);
            if($this->request->payload->eid==$eventJSON->eid){
                return json_decode($eventRaw,false);
            }
        }

        return [
                    "name"=>"Событие без названия",
                    "desc"=>'Это событие прилетело к нам из космоса, пожалуйста, обновите данные',
                    "start"=>date("U",mktime()),//"1555655800",
                    "end"=>date("U",mktime()),
                    "deadline"=>date("U",mktime()),
                    "maxUsers"=>"1",
                    "minUsers"=>"1",
                    "place"=>"59.557936, 30.091526",
                    "likes"=>"0",
                    "dislikes"=>"0",
                    "isFinished"=>"true",
                    "isAdministrative"=>"false",
                    "eid"=>"0",
                    "isStarted"=>"true",
                    "cid"=>"0",
                    "cName"=>"Категория скрыта",
                    "users"=>[["uid"=>"0","name"=>"Анонимный пользователь"],["uid"=>"0","name"=>"Пришелец Из Космоса"]],
                    "organisators"=>["uid"=>"0","name"=>"Пришелец Из Космоса"],
                    "coins"=>"0",
                    "comment"=>[],
        ];
    }

    public function getEvents(){
        $response=[];
        $eventsRange=$this->db->lrange("events",0, -1);
        foreach ($eventsRange as $arrayIndex => $eventRaw) {
            $response[]=json_decode($eventRaw);
        }

        return $response;
    }

    public function getUser(){

        if(!isset($this->request->payload->uid)){
            $this->request->payload->uid=$this->db->get("userId");
        }

        $userRange=$this->db->lrange("users",0,-1);
        foreach ($userRange as $indexKey => $userRaw) {
            $userJSON=json_decode($userRaw,false);
            if($userJSON->uid==$this->request->payload->uid || (isset($this->request->payload->token) && $this->request->payload->token==$userJSON->token)){

                return json_decode($userRaw,false);
            }
        }

        return ["uid"=>"0","age"=>"20","name"=>"Имя Скрыто","token"=>hash("sha256","Имя Скрыто"),"email"=>"noreply@gatchina.ru","ratingOrg"=>"0","ratingUser"=>"0","balance"=>"0"];

    }

    public function getCategories(){
        $response=[];
        $catRange=$this->db->lrange("categories",0, -1);
        foreach ($catRange as $catRecIndex => $catRawString) {
            $response[]=json_decode($catRawString,false);
        }

        return $response;
    }

    public function getCategory(){
        $response=[];
        if(!isset($this->request->payload->cid)){
            $this->request->payload->cid=$this->db->get("categoryId");
        }

        $catRange=$this->db->lrange("categories",0, -1);
        foreach ($catRange as $catRecIndex => $catRawString) {
            $json=json_decode($catRawString,false);
            if($json->cid==$this->request->payload->cid){
                return json_decode($catRawString,false);
            }
        }

        return $response;
    }

    public function createCategory(){
        $cid=$this->db->incr("categoryId");
        $this->request->payload->cid=$cid;

        $this->db->lpush("categories",json_encode($this->request->payload));

        return ["status"=>"200","text"=>"OK"];
    }

    public function createUser(){


        // DATA VALIDATION BEGIN

        // DATA VALIDATION ENDS
        $uid=$this->db->incr("userId");
        $this->request->payload->uid=$uid;

        // INITIALIZATION WITH ZEROS => BALANCE, RATINGS
        $this->request->payload->ratingOrg="0";
        $this->request->payload->ratingUser="0";
        $this->request->payload->balance="0";
        $this->request->payload->token=hash("sha256",$this->request->payload->name);;


        $this->db->lpush("users",json_encode($this->request->payload));

        return json_encode($this->request->payload);

    }

    public function createEvent(){
        // HERE GOES DATA VALIDATION 
        if( 
            !isset($this->request->payload->name) || empty($this->request->payload->name) ||
            !isset($this->request->payload->desc) || empty($this->request->payload->desc) ||
            !isset($this->request->payload->start) || empty($this->request->payload->start) ||
            !isset($this->request->payload->end) || empty($this->request->payload->end) ||
            !isset($this->request->payload->deadline) || empty($this->request->payload->deadline) ||
            // !isset($this->request->payload->place) || empty($this->request->payload->place) ||
            !isset($this->request->payload->cid) || empty($this->request->payload->cid) ||
            !isset($this->request->payload->organisators->uid) || empty($this->request->payload->organisators->uid)
          ){

            return ["status"=>"400","text"=>"Bad request. Not enough params","method"=>"createEvent"];

        }
        // HERE DATA VALIDATION ENDS


        $eid=$this->db->incr("eventId");

        // key with all events is events and it is a list
        $this->request->payload->eid=$eid;
        $this->request->payload->start=date("U",strtotime($this->request->payload->start));
        $this->request->payload->end=date("U",strtotime($this->request->payload->end));
        $this->request->payload->deadline=date("U",strtotime($this->request->payload->deadline));
        $this->request->payload->likes="0";
        $this->request->payload->dislikes="0";
        $this->request->payload->isFinished="false";
        $this->request->payload->isStarted="false";
        if(!isset($this->request->payload->place)){
            $this->request->payload->place="59.568456, 30.124473";
        }
        if(!isset($this->request->payload->cName)){

            // $this->request->payload->cid=$this->request->payload->cid;
            $this->request->payload->cName=$this->getCategory()->name;
        }
        $this->request->payload->users=[];
        $this->request->payload->comment=[];
        if(!isset($this->request->payload->coins)){
            $this->request->payload->coins="1";
        }
        if(!isset($this->request->payload->organisators->name)){
            $this->request->payload->uid=$this->request->payload->organisators->uid;
            $this->request->payload->organisators->name=$this->getUser()->name;
        }

        if(!isset($this->request->payload->maxUsers)){
            $this->request->payload->maxUsers="10";
        }
        if(!isset($this->request->payload->minUsers)){
            $this->request->payload->maxUsers="2";
        }
        // echo json_encode($this->request->payload);
        // echo "\n\n";
        // echo serialize(json_encode($this->request->payload));
        $this->db->lpush( "events", json_encode($this->request->payload) );
        $response=$this->request->payload;
        return $response;
    }

    public function participate(){

        if(!isset($this->request->payload->eid) || !isset($this->request->payload->uid)){
            return ["status"=>"400","text"=>"Bad request. Not enough params","method"=>"participate"];
        }

        $eventRange=$this->db->lrange("events",0,-1);
        foreach ($eventRange as $arrayIndex => $eventRaw) {
            $eventJSON=json_decode($eventRaw,false);
            if($eventJSON->eid==$this->request->payload->eid){
                if(!isset($eventJSON->usersId[$this->request->payload->uid])){

                        $eventJSON->users[]=[
                            "uid"=>$this->getUser()->uid,
                            "name"=>$this->getUser()->name,
                            "isHere"=>"false",
                        ];

                    $eventJSON->usersId[$this->request->payload->uid]="true";
                    $this->db->lset("events",$arrayIndex,json_encode($eventJSON));
                    return $this->getEvent();
                }

                return $this->getEvent();
            }
        }

        return ["status"=>"404","text"=>"User or Event not found","method"=>"participate"];

    }

    public function comment(){
        if(!isset($this->request->payload->eid) || !isset($this->request->payload->uid)){
            return ["status"=>"400","text"=>"Bad request. Not enough params","method"=>"comment"];
        }

        $eventRange=$this->db->lrange("events",0,-1);
        foreach ($eventRange as $arrayIndex => $eventRaw) {
            $eventJSON=json_decode($eventRaw,false);
            $eventJSONArr=json_decode($eventRaw,true);
            if($eventJSON->eid==$this->request->payload->eid){
                $userId=$this->request->payload->uid;

                if(isset($eventJSONArr["usersId"][$userId])){
                    $userData=$this->getUser();
                    if((int)($userData->uid)!=0){
                        $eventJSON->comment[]=[
                            "uid"=>$this->getUser()->uid,
                            "name"=>$this->getUser()->name,
                            "image"=>isset($this->request->payload->image)?$this->request->payload->image:"",
                            "text"=>$this->request->payload->text,
                            "createdTS"=>mktime(),
                        ];

                        $this->db->lset("events",$arrayIndex,json_encode($eventJSON));
                    }

                    return $this->getEvent();
                }

                return $this->getEvent();
            }
        }

        return ["status"=>"404","text"=>"User or Event not found","method"=>"comment"];
    }

    public function startParticipate(){

        if(!isset($this->request->payload->eid) || !isset($this->request->payload->uid)){
            return ["status"=>"400","text"=>"Bad request. Not enough params","method"=>"startParticipate"];
        }

        $eventRange=$this->db->lrange("events",0,-1);
        foreach ($eventRange as $arrayIndex => $eventRaw) {
            $eventJSON=json_decode($eventRaw,false);
            $eventJSONArr=json_decode($eventRaw,true);
            if($eventJSON->eid==$this->request->payload->eid){

                if(isset($eventJSONArr["usersId"][$this->request->payload->uid])){
                       foreach ($eventJSONArr["users"] as $key => $value) {

                            if($value["uid"]==$this->request->payload->uid){
                                $eventJSONArr["users"][$key]=[
                                    "uid"=>$this->getUser()->uid,
                                    "name"=>$this->getUser()->name,
                                    "isHere"=>"true",
                                ];

                            }
                        }

                    $this->db->lset("events",$arrayIndex,json_encode($eventJSONArr));
                    return $this->getEvent();
                }

                return $this->getEvent();
            }
        }

        return ["status"=>"404","text"=>"User or Event not found","method"=>"startParticipate"];

    }

    public function startEvent(){
        if(!isset($this->request->payload->eid)){
            return ["status"=>"400","text"=>"Bad request. Not enough params","called"=>"startEvent"];
        }
        $eventRange=$this->db->lrange("events",0,-1);
        foreach ($eventRange as $indexArray => $eventRaw) {
            $eventJSON=json_decode($eventRaw,false);
            if($eventJSON->eid==$this->request->payload->eid){
                $eventJSON->isStarted="true";
                $this->db->lset("events",$indexArray,json_encode($eventJSON));
                return $this->getEvent();
            }
        }
        return ["status"=>"404","Not found","called"=>"startEvent"];

    }

    public function finishEvent(){
        if(!isset($this->request->payload->eid)){
            return ["status"=>"400","text"=>"Bad request. Not enough params","called"=>"finishEvent"];

        }

        $eventRange=$this->db->lrange("events",0,-1);
        foreach ($eventRange as $indexArray => $eventRaw) {
            $eventJSON=json_decode($eventRaw,false);
            if($eventJSON->eid==$this->request->payload->eid){
                $eventJSON->isFinished="true";
                $this->db->lset("events",$indexArray,json_encode($eventJSON));
                return $this->getEvent();
            }
        }
            return ["status"=>"404","Not found","called"=>"finishEvent"];

    }

    public function addLikes(){
        if(!isset($this->request->payload->eid) || !isset($this->request->payload->uid)){
            return ["status"=>"400","text"=>"Bad request. Not enough params","called"=>"addLikes"];
        }
        $eventRange=$this->db->lrange("events",0,-1);
        foreach ($eventRange as $indexArray => $eventRaw) {
            $eventJSON=json_decode($eventRaw,false);
            if($eventJSON->eid==$this->request->payload->eid){
                $eventJSON->likes=(int)($eventJSON->likes)+1;
                $this->db->lset("events",$indexArray,json_encode($eventJSON));
                return $this->getEvent();
            }
        }
        return ["status"=>"404","Not found","called"=>"addLikes"];

    }

    public function addDislikes(){
        if(!isset($this->request->payload->eid) || !isset($this->request->payload->uid)){
            return ["status"=>"400","text"=>"Bad request. Not enough params","called"=>"addDislikes"];
        }
        $eventRange=$this->db->lrange("events",0,-1);
        foreach ($eventRange as $indexArray => $eventRaw) {
            $eventJSON=json_decode($eventRaw,false);
            if($eventJSON->eid==$this->request->payload->eid){
                $eventJSON->dislikes=(int)($eventJSON->dislikes)+1;
                $this->db->lset("events",$indexArray,json_encode($eventJSON));
                return $this->getEvent();
            }
        }
        return ["status"=>"404","Not found","called"=>"addDislikes"];

    }


    public function test(){
        $response=[];

        $reqMethod=$this->request->method;

        if($reqMethod=="test_createEvent"){ 
            $response=[
                "status"=>"200",
                "eid"=>"1029",
            ];
        }

        if($reqMethod=="test_getUser"){
            $response=[
                "name"=>"Тестовый Александр Петергофович",
                "age"=>"25",
                "token"=>hash("sha256","Тестовый Александр Петергофович"),
                "email"=>"a.test@supermail.ru",
                "uid"=>"99",
                "ratingOrg"=>"12",
                "ratingUser"=>"23",
                "balance"=>"7",
            ];
        }

        if($reqMethod=="test_getEvent"){
            $response=[
                "name"=>"Хакатон Гатчина",
                "desc"=>'Крутой хакатон для поиска идей развития "Умного города" и "Умного горожанина"',
                "start"=>"1558677600",
                "end"=>"1558898800",
                "deadline"=>"1558848800",
                "maxUsers"=>"250",
                "minUsers"=>"100",
                "place"=>"59.969675, 30.316701",
                "likes"=>"139",
                "dislikes"=>"2",
                "isFinished"=>"false",
                "isAdministrative"=>"true",
                "eid"=>"1029",
                "isStarted"=>"true",
                "cid"=>"2",
                "cName"=>"Администрация",
                "users"=>[["uid"=>"99","name"=>"Тестовый Александр Петергофович"],["uid"=>"98","name"=>"Ковальский Пингвин Леонидович"]],
                "organisators"=>["uid"=>"97","name"=>"Беловарский Антон Пальмович"],
                "coins"=>"15",
                "comment"=>[
                    [
                        "uid"=>"98",
                        "name"=>"Ковальский Пингвин Леонидович",
                        "image"=>"",
                        "text"=>"Хорошее начало хорошего хакатона",
                        "created"=>"1558771114",
                    ],
                    [
                        "uid"=>"99",
                        "name"=>"Тестовый Александр Петергофович",
                        "image"=>"",
                        "text"=>"Есть хочется",
                        "created"=>"1558781114",

                    ],
                    [
                        "uid"=>"98",
                        "name"=>"Ковальский Пингвин Леонидович",
                        "image"=>"",
                        "text"=>"А солнце уже садиться, мы всё кодим тра-ла-ла",
                        "created"=>"1558791114",
                    ],
                    [
                        "uid"=>"99",
                        "name"=>"Тестовый Александр Петергофович",
                        "image"=>"",
                        "text"=>"Сидим и пилим приложение",
                        "created"=>"1558808134",

                    ]
                ]
            ];
        }

        if($reqMethod=="test_getEvent" || $reqMethod=="test_comment" || $reqMethod=="test_participate" || $reqMethod=="test_startParticipate"){
            $response=[
                [
                    "name"=>"Хакатон Гатчина",
                    "desc"=>'Крутой хакатон для поиска идей развития "Умного города" и "Умного горожанина"',
                    "start"=>"1558677600",
                    "end"=>"1558898800",
                    "deadline"=>"1558848800",
                    "maxUsers"=>"250",
                    "minUsers"=>"100",
                    "place"=>"59.969675, 30.316701",
                    "likes"=>"139",
                    "dislikes"=>"2",
                    "isFinished"=>"false",
                    "isAdministrative"=>"true",
                    "eid"=>"1029",
                    "isStarted"=>"true",
                    "cid"=>"2",
                    "cName"=>"Администрация",
                    "users"=>[["uid"=>"99","name"=>"Тестовый Александр Петергофович"],["uid"=>"98","name"=>"Ковальский Пингвин Леонидович"]],
                    "organisators"=>["uid"=>"97","name"=>"Беловарский Антон Пальмович"],
                    "coins"=>"15",
                    "comment"=>[
                        [
                            "uid"=>"98",
                            "name"=>"Ковальский Пингвин Леонидович",
                            "image"=>"",
                            "text"=>"Хорошее начало хорошего хакатона",
                            "created"=>"1558771114",
                        ],
                        [
                            "uid"=>"99",
                            "name"=>"Тестовый Александр Петергофович",
                            "image"=>"",
                            "text"=>"Есть хочется",
                            "created"=>"1558781114",

                        ],
                        [
                            "uid"=>"98",
                            "name"=>"Ковальский Пингвин Леонидович",
                            "image"=>"",
                            "text"=>"А солнце уже садиться, мы всё кодим тра-ла-ла",
                            "created"=>"1558791114",
                        ],
                        [
                            "uid"=>"99",
                            "name"=>"Тестовый Александр Петергофович",
                            "image"=>"",
                            "text"=>"Сидим и пилим приложение",
                            "created"=>"1558808134",

                        ]
                    ],
                ],
                [
                    "name"=>"Установка скамейки",
                    "desc"=>'Устновка скамеек на площади Станислава Богданова',
                    "start"=>"1555655800",
                    "end"=>"1555685800",
                    "deadline"=>"1555585800",
                    "maxUsers"=>"45",
                    "minUsers"=>"80",
                    "place"=>"59.557936, 30.091526",
                    "likes"=>"567",
                    "dislikes"=>"24",
                    "isFinished"=>"true",
                    "isAdministrative"=>"true",
                    "eid"=>"1029",
                    "isStarted"=>"true",
                    "cid"=>"2",
                    "cName"=>"Администрация",
                    "users"=>[["uid"=>"99","name"=>"Тестовый Александр Петергофович"],["uid"=>"98","name"=>"Ковальский Пингвин Леонидович"]],
                    "organisators"=>["uid"=>"97","name"=>"Беловарский Антон Пальмович"],
                    "coins"=>"15",
                    "comment"=>[],
                ]
            ];
        }

        return $response;
    }
}


?>