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
include('lib/calc.php');
$f = new functions();
$c = new calc();

if(isset($_GET['usercode']))
{
	echo $f->getremoteconf($_GET['usercode']);
}
else
{
	if(!isset($_SESSION['uid']))
	{
		header('location: /');
	}
	$f->getuserstats();
	$f->getuser($_SESSION['uid']);
	if(!empty($_POST['remoteconf']))
	{
		$f->saveremoteconf($_POST['remoteconf']);
	}

	if(empty($f->user->usercode))
	{
		$contentdata['remoteurl'] = "Please configure your url in the profile section";
		$contentdata['value'] = file_get_contents('./sampleconf');
	}
	else
	{
		$contentdata['value'] = $f->getremoteconf($f->user->usercode);
		if(empty($contentdata['value']))
		{
			$contentdata['value'] = file_get_contents('./sampleconf');
		}
		$contentdata['remoteurl'] = "<a href='http://" . $_SERVER['SERVER_NAME'] . "/remote/" . $f->user->usercode . "' target='_blank'>http://" . $_SERVER['SERVER_NAME'] . "/remote/" . $f->user->usercode . "</a>";
	}
	$contentdata["news"] = $f->getnews();
	$contentdata["rigs"] = "(" . $f->countrigs() . " rigs and counting!)";
	$total_hash = 0;
	foreach($f->stats['rigs'] as $key => $value)
	{
		$hashes = explode(" ", $value['miner_hashes']);
		$total_hash += array_sum($hashes);
	}
	$price = $c->getprice("Ethereum");
	$profit = $c->geteth($total_hash);
	$contentdata['profiteth']=round($profit,4);
	$contentdata['profitbtc']=round($profit * $price[0]->price_btc,4);
	$contentdata['profitusd']=round($profit * $price[0]->price_usd,4);
	$contentdata['profiteur']=round($profit * $price[0]->price_eur,4);
	$contentdata['trackingcode']=$f->config->analytics;
	echo $f->getcontent('./templates/header.html',$contentdata);
	echo $f->getcontent('./templates/remoteconf.html',$contentdata);
	echo $f->getcontent('./templates/footer.html',$contentdata);
}


