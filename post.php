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
include('lib/functions.php');
$f = new functions();
$token = $_POST['token'];
$f->getuserbytoken($token);
if(!empty($f->user->id))
{
	$json = json_decode(file_get_contents($_FILES['data']['tmp_name']), 1);

	$hashes = explode(" ",$json['miner_hashes']);
	$mining_instance=$json['gpus'];
	foreach($hashes as $hash){
		if($hash==0)
		{
			$mining_instance--;
		}
	}
	$sql = "INSERT INTO hash (userid,date, rig, hash, miner_hashes, temp, fanrpm, rack_loc, ip, uptime, gpus) "
			. "values("
			. "'" . $f->user->id . "',"
			. "'" . date('Y-m-d H:i:00') . "',"
			. "'" . $json['hostname'] . "',"
			. "'" . $json['hash'] . "',"
			. "'" . $json['miner_hashes'] . "',"
			. "'" . $json['temp'] . "',"
			. "'" . $json['fanrpm'] . "',"
			. "'" . $json['rack_loc'] . "',"
			. "'" . $json['ip'] . "',"
			. "'" . $json['uptime'] . "',"
			. "'" . $json['gpus'] . "'"
			. ") "
			. "ON DUPLICATE KEY UPDATE "
			. "userid='" . $f->user->id . "', "
			. "date='" . date('Y-m-d H:i:00') . "', "
			. "rig='" . $json['hostname'] . "', "
			. "hash='" . $json['hash'] . "', "
			. "miner_hashes='" . $json['miner_hashes'] . "', "
			. "temp='" . $json['temp'] . "', "
			. "fanrpm='" . $json['fanrpm'] . "', "
			. "rack_loc='" . $json['rack_loc'] . "', "
			. "ip='" . $json['ip'] . "', "
			. "uptime='" . $json['uptime'] . "', "
			. "gpus='" . $json['gpus'] . "' ";
			



	$f->db->exec($sql);
	echo "Data Received.";
}