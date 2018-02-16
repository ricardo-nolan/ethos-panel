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
foreach($f->stats['rigs'] as $key=>$value){
	$value['miner_hashes']=implode(" ",array_map('round',explode(" ",$value['miner_hashes'])));
	$value['temp']=implode(" ",array_map('round',explode(" ",$value['temp'])));
	$value['fanrpm']=implode(" ",array_map(function($input) { return round($input / 1000); },explode(" ",$value['fanrpm'])));
	$table.="<tr>"
			. "<td>{$key} / {$value['rack_loc']}</td>"
			. "<td>{$value['ip']}</td>"
			. "<td>{$value['miner_instance']} / {$value['gpus']}</td>"
			. "<td>{$value['hash']}</td>"
			. "<td>{$value['miner_hashes']}</td>"
			. "<td>{$value['temp']}</td>"
			. "<td>{$value['fanrpm']}</td>"
			. "</tr>";
	$keys[] = "'" . $key . "'";
}
$contentdata["table"]=$table;
$contentdata["data"]=$f->getchart();
$contentdata["keys"]=implode(",",$keys);
$contentdata["news"]=$f->getnews();
echo $f->getcontent('./templates/main.html',$contentdata);



