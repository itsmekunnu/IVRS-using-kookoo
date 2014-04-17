<?php
session_start();
	
require_once("/home/public_html/kookoophp/response.php");//response.php is the kookoo xml preparation class file
$r = new Response();

$r->setFiller("yes");
      

$fileName="./kookoo_trace.log";// create logs to trace your application behaviour
if (file_exists($fileName))
{
        $fp = fopen($fileName, 'a+') or die("can't open file");
}
else
{
        $fp= fopen($fileName, 'x+');// or die("can't open file");
}
fwrite($fp,"----------- kookoo params ------------- \n ");
  foreach ($_REQUEST as $k => $v) {
 	 	fwrite($fp,"param --  $k =  $v \n ");
   } 
fwrite($fp,"----------- session params maintained -------------  \n");
     foreach ($_SESSION as $k => $v) {
	 	fwrite($fp,"session params $k =  $v  \n");
	}
 
if($_REQUEST['event']== "NewCall" ) 
{

fwrite($fp,"-----------NewCall from kookoo  -------------  \n");
	// Every new call first time you will get below params from kookoo
	//                                        event = NewCall
	//                                         cid= caller Number
	//                                         called_number = sid
	//                                         sid = session variable
	//    
	//You maintain your own session params store require data
	$_SESSION['caller_number']=$_REQUEST['cid'];
	$_SESSION['kookoo_number']=$_REQUEST['called_number']; 
	//called_number is register phone number on kookoo
	//
	$_SESSION['session_id']   = $_REQUEST['sid'];
	//sid is unique callid for each call
    // you maintain one session variable to check position of your call
    //here i had maintain next_goto as session variable
  $_SESSION['next_goto']='scode';
} 
if ($_REQUEST['event']=="Disconnect" || $_REQUEST['event']=="Hangup" ){
exit;
} 
// start

if($_SESSION['next_goto']=='scode'){
 	$collectInput = New CollectDtmf();
	$collectInput->addPlayText('Welcome to the Community school monitoring system',4);
	$collectInput->addPlayText('Please Enter the school code',4);
	$collectInput->setMaxDigits('3'); //max inputs to be allowed
	$collectInput->setTimeOut('4000');  //maxtimeout if caller not give any inputs
	$r->addCollectDtmf($collectInput);
    $_SESSION['next_goto']='scode_CheckInput';
}
else if($_REQUEST['event'] == 'GotDTMF' && $_SESSION['next_goto'] == 'scode_CheckInput' )
{
//input will come data param
//print parameter data value
	$c=mysql_connect('localhost','username','password');
		$sdb=mysql_select_db("name_of_database",$c);
		$v1=(int)$_REQUEST['data'];
		$result=mysql_query
		("SELECT * FROM school_db 
		WHERE sid=$v1"
		,$c);
		$row=mysql_fetch_array($result);
		$vy=(string)$row['s_name'];
	if($_REQUEST['data'] == '')
	{ //if value null, caller has not given any dtmf
		//no input handled
		 $r->addPlayText('you have not entered any input');
		 $_SESSION['next_goto']='scode';
	}
	else if($vy != '')
	{
		$_SESSION['sid']=$row['sid'];
		$_SESSION['s_name']=$row['s_name'];
		$_SESSION['next_goto']='Que1';
	}
	else
	{
		 $r->addPlayText('your input was invalid');
		 $_SESSION['next_goto']='scode';
	}
	mysql_close($c);
}


//1

else if($_SESSION['next_goto']=='Que1'){
 	$collectInput = New CollectDtmf();
	$vx=$_SESSION['s_name'];
	$collectInput->addPlayText("You have selected $vx.",4);
	$collectInput->addPlayText('Please answer the following questions',4);
	$collectInput->addPlayText('Was the teacher  present today?',4);
	$collectInput->addPlayText('Please press one if the teacher was present today, else press two if the teacher was absent today',4);
	$collectInput->setMaxDigits('1'); //max inputs to be allowed
	$collectInput->setTimeOut('4000');  //maxtimeout if caller not give any inputs
	$r->addCollectDtmf($collectInput);
    $_SESSION['next_goto']='Que1_CheckInput';
}
else if($_REQUEST['event'] == 'GotDTMF' && $_SESSION['next_goto'] == 'Que1_CheckInput' )
{
//input will come data param
//print parameter data value
	if($_REQUEST['data'] == '')
	{ 
		 $r->addPlayText('you have not entered any input');
		 $_SESSION['next_goto']='Que1';
	}
	else if($_REQUEST['data'] == '1' || $_REQUEST['data'] == '2' )
	{
		$_SESSION['que1'] = $_REQUEST['data'];
		//$r->addPlayText('We have received your Response');
		$_SESSION['next_goto']='Que2';
	}
	else
	{
		 $r->addPlayText('your input was invalid');
		 $_SESSION['next_goto']='Que1';
	}
}

// 2

else if($_SESSION['next_goto']=='Que2'){
 	$collectInput = New CollectDtmf();
	$collectInput->addPlayText('Your Second question is',4);
	$collectInput->addPlayText('Was mid-day meal served today?',4);
	$collectInput->addPlayText(' Please press one if the mid-day meal was served today, else press two if mid-day meal was not served today.',4);
	$collectInput->setMaxDigits('1'); //max inputs to be allowed
	$collectInput->setTimeOut('4000');  //maxtimeout if caller not give any inputs
	$r->addCollectDtmf($collectInput);
    $_SESSION['next_goto']='Que2_CheckInput';
}
else if($_REQUEST['event'] == 'GotDTMF' && $_SESSION['next_goto'] == 'Que2_CheckInput' )
{
//input will come data param
//print parameter data value
	if($_REQUEST['data'] == '')
	{ //if value null, caller has not given any dtmf
		//no input handled
		 $r->addPlayText('you have not entered any input');
		 $_SESSION['next_goto']='Que2';
	}
	else if($_REQUEST['data'] == '1' || $_REQUEST['data'] == '2' )
	{
		$_SESSION['que2'] = $_REQUEST['data'];
		//start session, session will be maintained for entire call
		$c=mysql_connect('localhost','username','password');
		//sdb is the selected database
		$sdb=mysql_select_db("name_of_database",$c);
		$v0=$_SESSION['sid'];
		$v1=(string)$_SESSION['caller_number'];
		$v2=date(Ymd);
		$v3=(int)$_SESSION['que1'];
		$v4=(int)$_SESSION['que2'];
		$result=mysql_query
		("INSERT INTO kookoo 
		VALUES($v0,$v1,$v2,$v3,$v4)"
		,$c);
		mysql_close($c);
		$r->addPlayText('We have received your Response');
		$r->addPlayText('Thank you for  information sharing, a team from A P V V U will follow up on the necessary information .');
		$r->addHangup();
	}
	else
	{
		 $r->addPlayText('your input was invalid');
		 $_SESSION['next_goto']='Que2';
	}
}
else 
{
	//print you session param 'next_goto' and other details
      $r->addPlayText('Sorry, session and events not maintained properly, Thank you for calling, have nice day');
      $r->addHangup();	// do something more or to send hang up to kookoo	
}

fwrite($fp,"----------- final xml send to kookoo  -------------  ".$r->getXML()."\n");
$r->send();
?>