<?php
/* Minified ftp client made in PHP
* Requires linux operating system
* Daemonz-ftp v0.1
* release date: 11-01-2022
*/
$currDir = "";
$pasvMode = true;
$connection = "";
$login = "";
$commandArr = NULL;
$mode = FTP_BINARY; //Transfer mode Binary by default
$helpCom = "
********************* HELP **********************

  bye                                 - Exit server
  cd <dir>                            - Change directory
  del                                 - Delete remote files
  deltree                             - Delete remote directory
  get <remote file>                   - Get remote file
  lls                                 - List local files
  lpwd                                - Return current local Directory
  lrm                                 - Delete local file
  ls                                  - List content in current directory
  mkdir <dir name>                    - Create a Directory
  Mode                                - Transfer mode: binary | ascii
  pasv <on/off>                       - Turn on and off passive mode
  put <local file>                    - Local file to upload
  pwd                                 - Return current remote directory
  rename <current name> <new name>    - Rename a remote file
  type                                - Return the system type of the remote server\n

";
echo "\n> Daemonz-FTP client v.01\n";
echo $helpCom;
$ftp_server = readline("Server IP: ");
$port = readline("Port(21): ");
if(trim($port) == "")
{
  $port = "21";
}
$uname = readline("Username: ");//Enter your ftp username here.
$pwd = readline("Password: ");//Enter your ftp password here.

echo "Connecting to ftp server $uname@$ftp_server:$port ...\n";
// Connect to remote server
try
{
  $connection = ftp_connect($ftp_server, $port) or die("Error connecting to $ftp_server");
  if (@ftp_login($connection, $uname, $pwd))
  {
    echo "Connected as $uname\n";
    echo "Transfer mode: BINARY\n";
  }
  else
  {
    echo "Couldn't connect as $uname\n";
    exit("Cannot connect to host ($ftp_server)");
  }
  //$login = ftp_login($connection, $uname, $pwd);
}
catch (Exception $e)
{
  echo 'An error occured: ', $e->getMessage(), "\n";
  exit("Cannot connect to host ($ftp_server)");
}

ftp_pasv($connection,TRUE);

while(true)
{
  $validCommand = false; //if stays false at the end of the loop, the command was invalid
  $command = readline("ftp> ");
  $commandArr = explode(" ",strtolower($command));

  if(count($commandArr) < 1 || $commandArr[0] == "help")
  {
    echo $helpCom;
    $validCommand = true;
  }
  else
  {
    //Validate the number of arguments required against for the command
    // and set args in $commandArr array
    if($commandArr[0] == "cd" && count($commandArr) == 2)
    {
      if (ftp_chdir($connection, $commandArr[1]))
      {
          echo "Current directory is now: " . ftp_pwd($connection) . "\n";
      }
      else
      {
          echo "Couldn't change directory\n";
      }
      $validCommand = true;
    }
    elseif($commandArr[0] == "cd")
    {
      echo "*** Error: cd requires 1 argument.\n";
      $validCommand = true;
    }
    if($commandArr[0] == "ls" && count($commandArr) == 1)
    {
      $contents = ftp_nlist($connection, ".");
      foreach ($contents as $value)
      {
        echo "$value\n";
      }
      $validCommand = true;
    }
    if($commandArr[0] == "get" && count($commandArr) == 2)
    {
      //if (ftp_get($connection, $commandArr[1], $commandArr[1], FTP_BINARY)) {
      if (ftp_get($connection, $commandArr[1], $commandArr[1], $mode))
      {
          echo "$commandArr[1] was downloaded successfully.\n";
      }
      else
      {
          echo "There was a problem downloading file $commandArr[1]\n";
      }
      $validCommand = true;
    }
    elseif($commandArr[0] == "get")
    {
      echo "*** Error: get requires 1 argument.\n";
      $validCommand = true;
    }
    if($commandArr[0] == "mkdir" && count($commandArr) == 2)
    {
      if (ftp_mkdir($connection, $commandArr[1]))
      {
       echo "successfully created $commandArr[1]\n";
      }
      else
      {
       echo "There was a problem while creating $commandArr[1]\n";
      }
      $validCommand = true;
    }
    elseif($commandArr[0] == "mkdir")
    {
      echo "*** Error: mkdir requires 1 argument.\n";
      $validCommand = true;
    }
    if($commandArr[0] == "bye")
    {
      ftp_close($connection);
      $validCommand = true;
      exit("Goodbye!");
    }
    if($commandArr[0] == "pwd" )
    {
      echo ftp_pwd($connection)."\n";
      $validCommand = true;
    }
    if($commandArr[0] == "lls")
    {
      $out = shell_exec('ls -la');
      echo $out."\n";
      $validCommand = true;
    }
    if($commandArr[0] == "lrm" && count($commandArr) == 2)
    {
      $out = shell_exec('rm -rf '.$commandArr[1]);
      if(trim($out) == "")
      {
        echo "$commandArr[1] has been deleted.\n";
      }
      else
      {
        echo $out."\n";
      }
      $validCommand = true;
    }
    elseif($commandArr[0] == "lrm")
    {
      echo "*** Error: lrm requires 1 argument.\n";
      $validCommand = true;
    }
    if($commandArr[0] == "lpwd")
    {
      $out = shell_exec('pwd');
      echo $out."\n";
      $validCommand = true;
    }
    if($commandArr[0] == "put" && count($commandArr) == 2)
    {
      if (ftp_put($connection, $commandArr[1], $commandArr[1], $mode))
      {
       echo "successfully uploaded $commandArr[1]\n";
      }
      else
      {
       echo "There was a problem while uploading file $commandArr[1]\n";
      }
      $validCommand = true;
    }
    elseif($commandArr[0] == "put")
    {
      echo "*** Error: put requires 1 argument.\n";
      $validCommand = true;
    }
    if($commandArr[0] == "del" && count($commandArr) == 2)
    {
      if (ftp_delete($connection, $commandArr[1]))
      {
       echo "$commandArr[1] deleted successful\n";
      }
      else
      {
       echo "could not delete $commandArr[1]\n";
      }
      $validCommand = true;
    }
    elseif($commandArr[0] == "del")
    {
      echo "*** Error: del requires 1 argument.\n";
      $validCommand = true;
    }
    if($commandArr[0] == "deltree" && count($commandArr) == 2)
    {
      if (ftp_rmdir($connection, $commandArr[1]))
      {
          echo "Successfully deleted $commandArr[1]\n";
      }
      else
      {
          echo "There was a problem while deleting $commandArr[1]\n";
      }
      $validCommand = true;
    }
    elseif($commandArr[0] == "deltree")
    {
      echo "*** Error: deltree requires 1 argument.\n";
      $validCommand = true;
    }
    if($commandArr[0] == "rename" && count($commandArr) == 3)
    {
      if (ftp_rename($connection, $commandArr[1], $commandArr[2]))
      {
       echo "successfully renamed $commandArr[1] to $commandArr[2]\n";
      }
      else
      {
       echo "There was a problem while renaming $commandArr[1] to $commandArr[2]\n";
      }
      $validCommand = true;
    }
    elseif($commandArr[0] == "rename")
    {
       echo "command rename requires 3 arguments.\n";
      $validCommand = true;
    }
    if($commandArr[0] == "pasv" && count($commandArr) == 2)
    {
      if($commandArr[1] == "true")
      {
        $pasvMode = true;
        ftp_pasv($connection, true);
        echo "Passive mode set to True\n";
      }
      else
      {
        $pasvMode = false;
        ftp_pasv($connection, false);
        echo "Passive mode set to False\n";
      }
      $validCommand = true;
    }
    elseif($commandArr[0] == "pasv" && count($commandArr) == 1)
    {
      if($pasvMode == true)
      {
        echo "Passive mode set to True\n";
      }
      else
      {
        echo "Passive mode set to False\n";
      }
      $validCommand = true;
    }
    if($commandArr[0] == "mode" && count($commandArr) == 2)
    {
      if($commandArr[1] == "binary")
      {
        $mode = FTP_BINARY;
        echo "Transfer mode set to BINARY\n";
      }
      else
      {
        $mode = FTP_ASCII;
        echo "Transfer mode set to ASCII\n";
      }
      $validCommand = true;
    }
    elseif($commandArr[0] == "mode")
    {
      // Display status
      if($mode == FTP_BINARY)
      {
        echo "Transfer mode set to BINARY\n";
      }
      else
      {
        echo "Transfer mode set to ASCII\n";
      }
      $validCommand = true;
    }
    if($commandArr[0] == "type")
    {
      // Display system type
      if ($type = ftp_systype($connection))
      {
          echo "$ftp_server is powered by $type\n";
      } else
      {
          echo "Couldn't get the systype\n";
      }
      $validCommand = true;
    }
  }
  if($validCommand == false)
  {
    echo "$commandArr[0]: Command invalid.\n";
  }
}
?>
