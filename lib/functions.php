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

class functions
{

	public $home_dir;
	public $config;
	public $db;
	public $output;
	public $user;
	public $stats;

	public function __construct()
	{
		ini_set("SMTP", "smtp.dynu.com");
		ini_set("sendmail_from", "info@ethos-panel.com");
		$this->home_dir = getcwd();
		$this->getconfig();
		try
		{
			$this->db = new PDO("mysql:host={$this->config->db_servername};dbname={$this->config->db_name}", $this->config->db_username, $this->config->db_password);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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

	public function getuser($uid)
	{
		$sql = "SELECT id,email,emailnotifications,dataorigin,datahash,url,usercode from users where id = :uid";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->bindParam(":uid", $uid);
			$stmt->execute();

			if($stmt->rowCount() > 0)
			{
				$user = $stmt->fetchObject();
				$this->user = $user;
			}
		}
	}

	public function getuserbytoken($token)
	{
		$sql = "SELECT id,email,emailnotifications,dataorigin,datahash,url,usercode from users where datahash = :datahash";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->bindParam(":datahash", $token);
			$stmt->execute();

			if($stmt->rowCount() > 0)
			{
				$user = $stmt->fetchObject();
				$this->user = $user;
			}
		}
	}

	public function getusers()
	{
		$sql = "SELECT id,email,emailnotifications,dataorigin,url from users";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->execute();

			if($stmt->rowCount() > 0)
			{
				return $stmt;
			}
		}
	}

	public function login($email, $password)
	{
		$sql = "SELECT id,password from users where email = :email";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->bindParam(":email", $email);
			$stmt->execute();

			if($stmt->rowCount() > 0)
			{
				$user = $stmt->fetchObject();
				if(password_verify($password, $user->password))
				{
					$_SESSION['uid'] = $user->id;
				}
				else
				{
					return false;
				}
			}
			else
			{
				$sql = "INSERT INTO users (email,password,datahash) values(:email,:password,:datahash)";
				if($stmt = $this->db->prepare($sql))
				{
					$hashedpassword = password_hash($password, PASSWORD_DEFAULT);

					$stmt->bindParam(":email", $email);
					$stmt->bindParam(":password", $hashedpassword);
					$token = bin2hex(openssl_random_pseudo_bytes(10));
					$stmt->bindParam(":datahash", $token);
					$stmt->execute();
					$_SESSION['uid'] = $this->db->lastInsertId();
				}
				else
				{
					return false;
				}
			}
		}
	}

	public function logout()
	{
		unset($_SESSION['uid']);
		session_destroy();
	}

	public function changepassword($password)
	{
		$sql = "UPDATE users set password=:password where id=:uid";
		if($stmt = $this->db->prepare($sql))
		{
			$hashedpassword = password_hash($password, PASSWORD_DEFAULT);
			$stmt->bindParam(":password", $hashedpassword);
			$stmt->bindParam(":uid", $_SESSION['uid']);
			$stmt->execute();
		}
		else
		{
			return false;
		}
	}

	public function saveprofile($dataorigin, $url, $emailnotifications)
	{
		$sql = "UPDATE users set dataorigin=:dataorigin, datahash=:datahash, url=:url, emailnotifications=:emailnotifications, usercode=:usercode where id=:uid";
		if($stmt = $this->db->prepare($sql))
		{
			$emailnotifications = $emailnotifications == 1 ? 1 : 0;
			$regex = '/http:\/\/([a-z0-9]{6}).*/';
			preg_match($regex, $url, $usercode);
			$stmt->bindParam(":emailnotifications", $emailnotifications);
			$stmt->bindParam(":dataorigin", $dataorigin);
			if(empty($this->user->datahash))
			{
				$token = bin2hex(openssl_random_pseudo_bytes(10));
				$stmt->bindParam(":datahash", $token);
			}
			else
			{
				$stmt->bindParam(":datahash", $this->user->datahash);
			}
			$stmt->bindParam(":url", $url);
			$stmt->bindParam(":usercode", $usercode[1]);
			$stmt->bindParam(":uid", $_SESSION['uid']);
			$stmt->execute();
		}
		else
		{
			return false;
		}
	}

	public function getuserstats($uid = 0)
	{
		$tstats = array();
		$this->getuser($uid > 0 ? $uid : $_SESSION['uid']);
		if(!empty($this->user->url) && $this->user->dataorigin == "0")
		{
			$this->stats = $this->makerequest($this->user->url, "", 1);
		}
		else
		{
			$sql = "SELECT * from hash where userid = :uid order by date desc limit 1";
			if($stmt = $this->db->prepare($sql))
			{
				$stmt->bindParam(":uid", $this->user->id);
				$stmt->execute();

				if($stmt->rowCount() > 0)
				{
					while($row = $stmt->fetch(PDO::FETCH_ASSOC))
					{
						$this->stats['rigs'][$row['rig']]=$row;
					}
				}
			}
		}
	}

	public function getstats()
	{
		$tstats = array();
		$stmt = $this->getusers();
		while($user = $stmt->fetchObject())
		{
			if(!empty($user->url) && $user->dataorigin == "0")
			{
				$this->stats = $this->makerequest($user->url, "", 1);
				if(isset($this->stats['rigs']) && !empty($this->stats['rigs']))
				{
					foreach($this->stats['rigs'] as $rig => $data)
					{
						$sql = "INSERT INTO hash (userid,date, rig, hash) "
								. "values("
								. "'" . $user->id . "',"
								. "'" . date('Y-m-d H:i:00') . "',"
								. "'" . $rig . "','" . $data['hash'] . "') "
								. "ON DUPLICATE KEY UPDATE "
								. "userid='" . $user->id . "', "
								. "date='" . date('Y-m-d H:i:00') . "', "
								. "rig='" . $rig . "', "
								. "hash='" . $data['hash'] . "'";
						if($this->db->exec($sql) !== TRUE)
						{
							echo "Error: " . $sql . "<br>" . print_r($this->db->errorInfo(), 1);
						}
					}
				}
			}
		}
		$sql = "DELETE from hash WHERE DATE(date) < DATE(NOW() - INTERVAL 7 DAY)";
		if($this->db->exec($sql) !== TRUE)
		{
			echo "Error: " . $sql . "<br>" . print_r($this->db->errorInfo(), 1);
		}
	}

	public function countrigs()
	{
		$sql = "SELECT count(distinct rig) as rigs from hash";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->execute();

			$count = $stmt->fetchObject();
			return $count->rigs;
		}
	}

	public function checkrigs()
	{
		$stmt = $this->getusers();
		while($user = $stmt->fetchObject())
		{
			$this->getuserstats($user->id);
			if(!empty($this->stats['rigs']))
			{
				$data = array();
				foreach($this->stats['rigs'] as $key => $value)
				{
					$hashes = explode(" ", $value['miner_hashes']);
					foreach($hashes as $hash)
					{
						$gpucrash = $hash <= $this->config->minimumhash ? true : false;
					}
				}
				if(date('m') % 15 && $gpucrash == true && $this->user->emailnotifications == 1)
				{
					mail($this->user->email, "[Ethos-Panel] GPU Crash", "Ethos-Panel has detected that one of your GPUs has crashed");
				}
			}
		}
	}

	public function getchart($range = 1)
	{
		$sql = "SELECT * from hash where userid = :uid and DATE(date) > DATE(NOW() - INTERVAL :range DAY)";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->bindParam(":uid", $this->user->id);
			$stmt->bindParam(":range", $range);
			$stmt->execute();

			if($stmt->rowCount() > 0)
			{

				$stats = array();
				$counter = -1;
				$tdate = "";
				while($row = $stmt->fetchObject())
				{
					if($tdate != $row->date)
					{
						$counter++;
						$tdate = $row->date;
					}
					$stats[$counter]['date'] = date('Y-m-d H:i', strtotime($row->date));
					$stats[$counter][$row->rig] = $row->hash;
				}
				return json_encode($stats);
			}
		}
	}

	public function getcontent($file, $data = null)
	{
		$template = file_get_contents($file);
		if($data != null)
		{
			foreach($data as $key => $value)
			{
				$template = str_replace('<!--%%' . $key . '%%-->', $value, $template);
			}
		}

		return $template;
	}

	public function getnews()
	{
		$sql = "SELECT * from news order by date desc limit 0,10";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->execute();

			if($stmt->rowCount() > 1)
			{
				$news = "";
				while($row = $stmt->fetchObject())
				{
					$date = explode(" ", $row->date);
					$news .= "<li>[" . $date[0] . "] " . $row->content . "</li>";
				}
				return $news;
			}
			else
			{
				return false;
			}
		}
	}

	public function getremoteconf($usercode)
	{
		$sql = "SELECT conf from remoteconf join users on remoteconf.userid=users.id where users.usercode=:usercode";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->bindParam(":usercode", $usercode);
			$stmt->execute();

			if($stmt->rowCount() == 1)
			{
				$row = $stmt->fetchObject();
				return $row->conf;
			}
			else
			{
				return false;
			}
		}
	}

	public function saveremoteconf($conf)
	{

		$sql = "INSERT into remoteconf (userid,conf) values(:uid,:conf) "
				. "ON DUPLICATE KEY UPDATE conf=:conf";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->bindParam(":uid", $this->user->id);
			$stmt->bindParam(":conf", $conf);
			$stmt->execute();
		}
		else
		{
			return false;
		}
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
