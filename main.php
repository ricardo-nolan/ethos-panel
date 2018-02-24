<?php

/* 
 * The MIT License
 *
 * Copyright 2018 foraern.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
session_start();
include('lib/functions.php');
$f=new functions();
if(!isset($_SESSION['uid'])){
	header('location: /');
}
$f->getuser($_SESSION['uid']);
$f->getuserstats();
$table="";
if(!empty($f->stats['rigs']))
{
	foreach($f->stats['rigs'] as $key=>$value){
		$keys[] = "'" . $key . "'";
	}
	$contentdata["keys"]=implode(",",$keys);
	
}

$contentdata["data"]=$f->getchart();
$contentdata["news"]=$f->getnews();
$contentdata["rigs"]="(".$f->countrigs()." rigs and counting!)";
if(empty($f->user->url)){
	$contentdata["urlwarning"]="<h4>Please provide your ethos url in your <a href='./profile.php'>profile</a></h4>";
}
else{
	$contentdata["urlwarning"]="";
}
echo $f->getcontent('./templates/main.html',$contentdata);



