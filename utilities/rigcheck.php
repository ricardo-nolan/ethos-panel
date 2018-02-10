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


class rigcheck
{

	public $rig;
	public $output;
	public $current;
	public $failedgpu;
	public $firstrun;

	public function __construct()
	{
		$this->rig = gethostname();
		if(file_exists(__DIR__ . "/rigcheck.log"))
		{
			if(filesize(__DIR__ . "/rigcheck.log") > 500000)
			{
				shell_exec('sudo cp ' . __DIR__ . '/rigcheck.log ' . __DIR__ . '/rigcheck.old');
				file_put_contents(__DIR__ . '/rigcheck.log', '');
			}
		}
		if(file_exists(__DIR__ . '/activity.json'))
		{
			$activity = json_decode(file_get_contents(__DIR__ . '/activity.json'));
			$this->current = $activity->current;
			$this->failedgpu = $activity->failed;
		}
		else
		{
			$this->failedgpu = 0;
			$this->firstrun = true;
		}
		
		$this->rigcheck();

		$activity = array("current" => $this->current, "failed" => $this->failedgpu);
		file_put_contents(__DIR__ . "/rigcheck.log",$this->output,FILE_APPEND);
		file_put_contents(__DIR__ . '/activity.json', json_encode($activity));
	}

	public function rigcheck()
	{
		$hashfile = shell_exec('sudo tail -1 /var/run/ethos/miner_hashes.file');
		$hashes = explode(" ", $hashfile);
		$totalhash = 0;
		foreach($hashes as $key => $value)
		{
			if($value < 1)
			{
				$gpudead = true;
			}
			$totalhash += $value;
		}
		if($gpudead > 0)
		{
			if($this->failedgpu > 5)
			{
				$this->failedgpu = 0;
				$this->output .= "[" . date('H:i:s') . "] A GPU is failing: " . $hashfile;
				$msg = 'EthOS Rig ( ' . $this->rig . ' ) Rebooted.....' . $hashfile;
				$this->notify($msg);
				shell_exec('sudo reboot');
			}
			else
			{
				$this->output .= "[" . date('H:i:s') . "] A GPU is failing: " . $hashfile;
				$this->failedgpu++;
			}
		}
		else
		{
			$this->output .= "[" . date('H:i:s') . "] All GPUs are working: " . $hashfile;
			$this->failedgpu = 0;
		}
		$this->output .= "[" . date('H:i:s') . "] Current Temp: " . $this->stats->rigs->{$this->rig}->temp . "\n";
		$this->output .= "[" . date('H:i:s') . "] Hash Speed: " . $totalhash . " MH/s\n\n";
	}

}

