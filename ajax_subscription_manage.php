<?php

namespace ProcessWire;

/**
 * 
 * Add / remove / update subscriptions
 */

$response='';

if(!wire('user')->isLoggedin()){
	die('Error. You must be logged in subscribe to notifications');
}


// we get the subs object from the JS
$subscription_json = trim(file_get_contents("php://input"));

// TODO. We should do some validation here
$subscription_object = json_decode($subscription_json, true);

// Feels a bit of a waste but we want a few other bits of info so let's build our
// own array. 
$params=[];
$params['subscription']=$subscription_object;
$params['user_key']=wire('user')->id;
$params['authToken']=$subscription_object['authToken'];
$params['contentEncoding']=$subscription_object['contentEncoding'];
$params['endpoint']=$subscription_object['endpoint'];
$params['subscription_json']=$subscription_json;

if($subscription_object['action']=='PUT'){
	$result=$this->subscription_update($params);
}else if($subscription_object['action']=='DELETE'){
	$result=$this->subscription_delete($params);
}else{
	$result=$this->subscription_add($params);
}

// this is the kind of thing that is sent.
// action: put / delete / add
// authToken	"ABUywzOapjCy9OkLztFJ/g=="
// contentEncoding	"aesgcm"
// endpoint	"https://updates.push.services.mozilla.com/wpush/v2/gAAAAABmqPQaSt8w_1bv5PM_gURMCS9jxHT9_BNBUFOpool6cTM5oXl08-gSlLQF6829O8oiKjSRHviPZRBFxdRx204FxorgBhgO-CmgUMOCLKGsJVAFIke3AQI5TzUXat_YYOD8Q_zuv3DZuFU7yrifIkVh6MZ27wQtIfuitcLXphGhDk2eNRc"
// publicKey dfgsdfgsdgsdgdfg

echo $response;
exit(0);
