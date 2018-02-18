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
		$this->home_dir = getcwd();
		$this->getconfig();
		$this->db = new mysqli($this->config->db_servername, $this->config->db_username, $this->config->db_password, $this->config->db_name);
		if($this->db->connect_error)
		{
			die("Connection failed: " . $this->db->connect_error);
		}
	}

	public function __destruct()
	{
		$this->db->close();
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
		$sql = "SELECT id,email,url,usercode from users where id = ?";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->bind_param("i", $uid);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{
				$user = $result->fetch_object();
				$this->user = $user;
			}
		}
	}

	public function getusers()
	{
		$sql = "SELECT id,email,url from users";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{
				return $result;
			}
		}
	}

	public function login($email, $password)
	{
		$sql = "SELECT id,password from users where email = ?";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->bind_param("s", $email);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{
				$user = $result->fetch_object();
				if(password_verify($password, $user->password))
				{
					$_SESSION['uid'] = $user->id;
				}
				else
				{
					echo "failed login";
					return false;
				}
			}
			else
			{
				$sql = "INSERT INTO users (email,password) values(?,?)";
				if($stmt = $this->db->prepare($sql))
				{
					$hashedpassword = password_hash($password, PASSWORD_DEFAULT);
					$stmt->bind_param("ss", $email, $hashedpassword);
					$stmt->execute();
					$_SESSION['uid'] = $stmt->insert_id;
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
		$sql = "UPDATE users set password=? where id=?";
		if($stmt = $this->db->prepare($sql))
		{
			$hashedpassword = password_hash($password, PASSWORD_DEFAULT);
			$stmt->bind_param("si", $hashedpassword, $_SESSION['uid']);
			$stmt->execute();
		}
		else
		{
			return false;
		}
	}

	public function saveprofile($url)
	{
		$sql = "UPDATE users set url=?, usercode=? where id=?";
		if($stmt = $this->db->prepare($sql))
		{
			$regex = '/http:\/\/([a-z0-9]{6}).*/';
			preg_match($regex, $url, $usercode);
			$stmt->bind_param("ssi", $url, $usercode[1], $_SESSION['uid']);
			$stmt->execute();
		}
		else
		{
			return false;
		}
	}

	public function getuserstats()
	{
		$tstats = array();
		$this->getuser($_SESSION['uid']);
		$this->stats = $this->makerequest($this->user->url, "", 1);
	}

	public function getstats()
	{
		$tstats = array();
		$result = $this->getusers();
		while($user = $result->fetch_object())
		{
			if(!empty($user->url))
			{
				$this->stats = $this->makerequest($user->url, "", 1);
				foreach($this->stats['rigs'] as $rig => $data)
				{
					$sql = "INSERT INTO hash (userid,date, rig, hash) "
							. "values("
							. "'" . $user->id . "',"
							. "'" . date('Y-m-d H:i:00') . "',"
							. "'" . $rig . "','" . $data['hash'] . "') "
							. "ON DUPLICATE KEY UPDATE "
							. "userid='" . $user->id . "', "
							. "date='" . date('d-m-Y H:i') . "', "
							. "rig='" . $rig . "', "
							. "hash='" . $data['hash'] . "'";
					if($this->db->query($sql) !== TRUE)
					{
						echo "Error: " . $sql . "<br>" . $this->db->error;
					}
				}
			}
		}
		$sql = "DELETE from hash WHERE DATE(date) < DATE(NOW() - INTERVAL 7 DAY)";
		if($this->db->query($sql) !== TRUE)
		{
			echo "Error: " . $sql . "<br>" . $this->db->error;
		}
	}
	
	public function countrigs()
	{
		$sql = "SELECT count(distinct rig) as rigs from hash";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->execute();
			$result = $stmt->get_result();
			$count = $result->fetch_object();
			return $count->rigs;
		}
	}

	public function getchart()
	{
		$sql = "SELECT * from hash where userid = ?";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->bind_param("s", $this->user->id);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows > 0)
			{

				$stats = array();
				$counter = -1;
				$tdate = "";
				while($row = $result->fetch_object())
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
			$result = $stmt->get_result();
			if($result->num_rows > 1)
			{
				$news="";
				while($row = $result->fetch_object())
				{
					$date=explode(" ",$row->date);
					$news.="<li>[".$date[0]."] ".$row->content."</li>";
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
		$sql = "SELECT conf from remoteconf join users on remoteconf.userid=users.id where users.usercode=?";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->bind_param("s", $usercode);
			$stmt->execute();
			$result = $stmt->get_result();
			if($result->num_rows == 1)
			{
				$row = $result->fetch_object();
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

		$sql = "INSERT into remoteconf (userid,conf) values(?,?) "
				. "ON DUPLICATE KEY UPDATE conf=?";
		if($stmt = $this->db->prepare($sql))
		{
			$stmt->bind_param("iss", $this->user->id, $conf, $conf);
			$stmt->execute();
		}
		else
		{
			return false;
		}
	}

	public
			function makerequest($url, $data = "", $json = false)
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
