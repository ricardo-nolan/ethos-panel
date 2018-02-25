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

class calc
{

	public $home_dir;
	public $config;
	public $db;
	public $output;

	public function __construct()
	{
		$this->home_dir = getcwd();
		$this->getconfig();
		try
		{
			$this->db = new PDO("mysql:host={$this->config->db_servername};dbname={$this->config->db_name}", $this->config->db_username, $this->config->db_password);
		}
		catch(PDOException $e)
		{
			die("Connection failed: " . $e->getMessage());
		}
	}

	public function getconfig()
	{
		if(file_exists($this->home_dir . '/config/config.json'))
		{
			$this->config = json_decode(file_get_contents($this->home_dir . '/config/config.json'));
		}
		else
		{
			$this->output .= "[" . date('H:i:s') . "] Missing a config.json (" . $this->home_dir . '/config/config.json' . ") file, please use the config.sample.json file as a template.\n";
			return;
		}
	}

	public function getstats()
	{
		$today=date('Y-m-d');
		$sql = "SELECT date,BlockReward,Difficulty from blockinfo where date = :date";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->bindParam(":date", $today);
			$stmt->execute();

			if($stmt->rowCount() == 0)
			{
				$stats = $this->makerequest("https://www.coinwarz.com/v1/api/profitability/?apikey=" . $this->config->coinwarz_api . "&algo=ethash");
				$sql = "INSERT into blockinfo (date,BlockReward,Difficulty) values(:date,:blockreward,:difficulty)";
				if($stmt = $this->db->prepare($sql))
				{
					foreach($stats->Data as $key=>$blockinfo){
						if($blockinfo->CoinTag=="ETH"){
							$blockinfo->Difficulty = round($blockinfo->Difficulty / 10**12,4);
							$stmt->bindParam(":date", $today);
							$stmt->bindParam(":blockreward", $blockinfo->BlockReward);
							$stmt->bindParam(":difficulty", $blockinfo->Difficulty);
							$stmt->execute();
						}
					}
					return $blockinfo;
				}
				else
				{
					return false;
				}
			}
			else{
				$blockinfo = $stmt->fetchObject();
				return $blockinfo;
			}
		}
	}
	
	public function getprice($coin="Ethereum")
	{
		return $this->makerequest("https://api.coinmarketcap.com/v1/ticker/".$coin."/?convert=EUR");
	}

	public function geteth($userhash=0)
	{
		$coin = $this->getstats();
		$earnings=0;
		$earnings=((($userhash / 10**6) * $coin->BlockReward) / ($coin->Difficulty)) * 3600 * 24;
		return $earnings;
	}

	public function makerequest($url, $data = "", $json = false)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		if($data != "")
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$result = curl_exec($ch);
		curl_close($ch);
		return json_decode($result, $json);
	}

}
