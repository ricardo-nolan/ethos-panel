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

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$config = '{
	"autofan": 0,
	"rigcheck": 0,
	"db_servername": "' . $_POST['db_servername'] . '",
	"db_username": "' . $_POST['db_username'] . '",
	"db_password": "' . $_POST['db_password'] . '",
	"db_name": "' . $_POST['db_name'] . '"
	}';
	if(!file_put_contents('../config/config.json', $config))
	{
		die("Error writing config file <br /> <h3>Please run 'sudo chown -R www-data:www-data /var/www/html'</h3>");
	}
	// Create connection
	$conn = new mysqli($_POST['db_servername'], $_POST['db_username'], $_POST['db_password']);
	// Check connection
	if($conn->connect_error)
	{
		die("Connection failed: " . $conn->connect_error . "<br />");
	}
	else
	{
		echo "Connection Successful <br />";
	}

	// Create database
	$sql = "CREATE DATABASE `" . $_POST['db_name'] . "`";
	if($conn->query($sql) === TRUE)
	{
		echo "Database created successfully <br />";
		$conn->select_db($_POST['db_name']);
	}
	else
	{
		die("Error creating database: " . $conn->error . "<br />");
	}

	if($sqlfile = file_get_contents('../ethos-panel.sql'))
	{
		$var_array = explode(';', $sqlfile);
		foreach($var_array as $sql)
		{
			if($conn->query($sql) === TRUE)
			{
				//import successful
			}
			else
			{
				die("Error importing database: " . $conn->error ."<br />");
			}
		}
	}

	echo "<h2>If no errors are displayed above, please delete /install folder</h2>";

	$conn->close();
}
else
{
	?>
	<!DOCTYPE html>
	<html lang="en">

		<head>

			<meta charset="utf-8">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<meta name="description" content="">
			<meta name="author" content="">

			<title>Ethos Panel</title>

			<!-- Bootstrap Core CSS -->
			<link href="/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

			<!-- MetisMenu CSS -->
			<link href="/assets/vendor/metisMenu/metisMenu.min.css" rel="stylesheet">

			<!-- Custom CSS -->
			<link href="/assets/dist/css/sb-admin-2.css" rel="stylesheet">

			<!-- Custom Fonts -->
			<link href="/assets/vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

			<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
			<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
			<!--[if lt IE 9]>
				<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
				<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
			<![endif]-->

		</head>

		<body>

			<div class="container">
				<div class="row">
					<div class="col-md-4 col-md-offset-4">
						<div class="login-panel panel panel-default">
							<div class="panel-heading">
								<h3 class="panel-title">Installation</h3>
							</div>
							<div class="panel-body">
								<form role="form" method="post">
									<fieldset>
										<div class="form-group">
											<input class="form-control" placeholder="Database Hostname (ie. localhost)" name="db_servername" type="text" autofocus value="localhost">
										</div>
										<div class="form-group">
											<input class="form-control" placeholder="Database Username" name="db_username" type="text" autofocus>
										</div>
										<div class="form-group">
											<input class="form-control" placeholder="Database Password" name="db_password" type="password" autofocus>
										</div>
										<div class="form-group">
											<input class="form-control" placeholder="Database Name (ie. ethos-panel)" name="db_name" type="text" autofocus value="ethos-panel">
										</div>
										<!-- Change this to a button or input when using this as a form -->
										<input type="submit" class="btn btn-lg btn-success btn-block" value="Install" />
									</fieldset>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- jQuery -->
			<script src="/assets/vendor/jquery/jquery.min.js"></script>

			<!-- Bootstrap Core JavaScript -->
			<script src="/assets/vendor/bootstrap/js/bootstrap.min.js"></script>

			<!-- Metis Menu Plugin JavaScript -->
			<script src="/assets/vendor/metisMenu/metisMenu.min.js"></script>

			<!-- Custom Theme JavaScript -->
			<script src="/assets/dist/js/sb-admin-2.js"></script>

		</body>

	</html>
	<?php
}



