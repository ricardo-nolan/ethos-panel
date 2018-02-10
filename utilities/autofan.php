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

class autofan
{

	public $rig;
	public $config;
	public $temps;
	public $temprange;
	public $local;
	public $output;

	public function __construct()
	{
		$this->rig = gethostname();
		$this->temprange=array("10"=>30,"20"=>30,"30"=>40,"40"=>50,"50"=>60,"60"=>80,"65"=>90,"70"=>100, "80"=>100,"90"=>100,"100"=>100);
		if(file_exists(__DIR__ . "/autofan.log"))
		{
			if(filesize(__DIR__ . "/autofan.log") > 500000)
			{
				shell_exec('sudo cp ' . __DIR__ . '/autofan.log ' . __DIR__ . '/autofan.old');
				file_put_contents(__DIR__ . '/autofan.log', '');
			}
		}
		$this->local = explode("\n", file_get_contents('/home/ethos/local.conf'));
		$this->temps = shell_exec("/opt/ethos/sbin/ethos-readdata temps");
		$this->output .= "[" . date('H:i:s') . "] Temp: " . $this->temps . "\n";
		$this->setfan();
		file_put_contents(__DIR__ . "/autofan.log",$this->output,FILE_APPEND);
	}

	public function setfan()
	{
		$speed = array();
		$temp = explode(" ", $this->temps);
		foreach($temp as $key => $value)
		{
			$speed[$key] = $this->adjustspeed($value);
		}
		foreach($this->local as $key => $value)
		{
			$line = explode(" ", $value);
			if($line[0] == "fan" && $line[1] == $this->rig)
			{
				$this->output .= "[" . date('H:i:s') . "] Old Setting: " . $value . "\n";
				$this->local[$key] = "fan " . $this->rig . " " . implode(" ", $speed);
			}
		}
		$this->output .= "[" . date('H:i:s') . "] New Setting: fan " . $this->rig . " " . implode(" ", $speed) . "\n";
		file_put_contents('/home/ethos/local.conf', implode("\n", $this->local));
		shell_exec('/opt/ethos/bin/clear-thermals');
	}

	public function adjustspeed($temp)
	{
		foreach($this->temprange as $range => $newspeed)
		{
			if((int) $temp > (int) $range)
			{
				$speed = $newspeed;
			}
		}
		return $speed;
	}
}
