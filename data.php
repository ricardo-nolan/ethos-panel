<?php

/*
 * The MIT License
 *
 * Copyright 2018 ricardonolan.
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
$f->getuser($_SESSION['uid']);
if(isset($_GET['getchart']) && $_GET['getchart'] == "true")
{
	echo $f->getchart($_GET['range']);
}
if(isset($_GET['gettable']) && $_GET['gettable'] == "true")
{
	$f->getuserstats();
	if(!empty($f->stats['rigs']))
	{
		$data = array();
		foreach($f->stats['rigs'] as $key => $value)
		{
			$value['miner_hashes'] = implode(" ", array_map('round', explode(" ", $value['miner_hashes'])));
			$value['temp'] = implode(" ", array_map('round', explode(" ", $value['temp'])));
			$value['fanrpm'] = implode(" ", array_map(function($input)
					{
						return round($input / 1000);
					}, explode(" ", $value['fanrpm'])));
			$data["data"][] = array(
				$key . " / " . $value['rack_loc'],
				"<a href='".$value['ip']."' target='_blank'>".$value['ip']."</a>",
				$value['miner_instance'] . " / " . $value['gpus'],
				$value['hash'],
				$value['miner_hashes'],
				$value['temp'],
				$value['fanrpm']);
		}
		echo json_encode($data, 1);
	}
}

if(isset($_GET['getrigcount']) && $_GET['getrigcount'] == "true")
{
	echo "(" . $f->countrigs() . " rigs and counting!)";
}

if(isset($_GET['getprofit']) && $_GET['getprofit'] == "true")
{
	$f->getuserstats();
	$total_hash = 0;
	foreach($f->stats['rigs'] as $key => $value)
	{
		$hashes = explode(" ", $value['miner_hashes']);
		$total_hash += array_sum($hashes);
	}
	$price = $c->getprice("Ethereum");
	$profit = $c->geteth($total_hash);
	$currency=(isset($_GET['currency'])&&$_GET['currency']!="")?$_GET['currency']:"eth";
	switch($currency)
	{
		case "btc":
			$curprice=$price[0]->price_btc;
			break;
		case "usd":
			$curprice=$price[0]->price_usd;
			break;
		case "eur":
			$curprice=$price[0]->price_eur;
			break;
		default:
			$currency="eth";
			$curprice=1;
			break;
	}
	echo round($profit * $curprice, 4);
}

