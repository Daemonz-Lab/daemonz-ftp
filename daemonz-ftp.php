<?php
/* Minified ftp client made in PHP
* Requires linux operating system
* Daemonz-ftp v0.2
*/
$pasvMode = "off";
$connection = "";
$port = "21"; //default
$login = "";
$commandArr = NULL;
$mode = FTP_BINARY;
$commandSplit = "";
$useSSL = false;
$verbose = "off";
$helpCom = "
********************* HELP **********************

  help                                - list commands
  bye                                 - Exit server
  cd <dir>                            - Change directory
  del                                 - Delete remote files
  deltree /<directory>                - Delete remote directory
  get <local file> <remote file>      - Get remote file
  lls                                 - List local files
  lpwd                                - Return current local Directory
  lrm                                 - Delete local file
  ls <dir> <recursive:TRUE>           - List content of directory. Add TRUE for recursive listing;
                                        ie. ls / TRUE
  mkdir <dir name>                    - Create a Directory
  mode                                - Transfer mode: binary | ascii
  pasv <on|off>                       - Turn on and off passive mode
  put <remote file> <local file>      - Local file to upload
  pwd                                 - Return current remote directory
  rename <current file> <new file>    - Rename a remote file
  type                                - Return the system type of the remote server
  lcat <local file>                   - Read local file
  cat <remote file>                   - Read remote file
  chmod <perm> <file|directory>       - Set permission on file/directory on remote server;
                                        ie. chmod 777 file.txt
  verb <on|off>                       - Set verbose; off(default) on(debug mode)\n
";
/*
* Array commandArr is a tridimensional array built to centralize commands and parameters in a structured manner.
* Dimension 1: Contains all commands used in CLI.
* Dimension 2: Contains the command names, numbers of arguments expected for the command, array of parameters
*  for the command (dimension 3), andreturn value type expected from the command and a custom error message for the command.
* Dimension 3: Contains the PHP FTP functions and parameters for those functions. index1=native function,
*  index2=ftp connector variables and the following are parameters. When filled, Indexes used for parameters are default values.
*/
$commandsArr = array(
  array("cd",1,array("ftp_chdir","!conn",""),"bool","Error: Directory not found.\n"),
  array("get",3,array("ftp_get","!conn","","",$mode),"bool","Error: There was a problem downloading file.\n"),
  array("put",3,array("ftp_put","!conn","","",$mode),"bool","Error: There was a problem uploading file.\n"),
  array("mkdir",1,array("ftp_mkdir","!conn",""),"bool","Error: Cannot create directory.\n"),
  array("del",1,array("ftp_delete","!conn",""),"bool","Error: Cannot remove remote file.\n"),
  array("deltree",1,array("ftp_rmdir","!conn",""),"bool","Error: Cannot remove remote directory.\n"),
  array("rename",2,array("ftp_rename","!conn","",""),"bool","Error: Cannot rename remote file/directory.\n"),
  array("mode",1,array("transFunc","!conn","status"),"bool","Error: Cannot display/set mode.\n"),
  array("type",0,array("ftp_systype","!conn"),"string","Error: Cannot show system type.\n"),
  array("bye",0,array("endSession"),"bool","Error: Cannot disconnect from session.\n"),
  array("exit",0,array("endSession"),"bool","Error: Cannot disconnect from session.\n"),
  array("pasv",1,array("pasvFunc","!conn","status"),"bool","Error: Cannot display or set passive mode.\n"),
  array("pwd",0,array("ftp_pwd","!conn"),"string","Error: Cannot locate remote directory.\n"),
  array("lpwd",0,array("shellExec","pwd","","",""),"string","Error: Cannot locate local directory.\n"),
  array("lls",0,array("shellExec","ls -la","","",""),"string","Error: Cannot list local directory.\n"),
  array("lrm",1,array("shellExec","rm -rf","","",""),"string","Error: Cannot remove local file/directory.\n"),
  array("cat",1,array("catFileFunc","!conn",""),"string","Error: Cannot read remote file.\n"),
  array("lcat",1,array("shellExec","cat","","",""),"string","Error: Cannot read local file.\n"),
  array("chmod",2,array("chmodFunc","!conn","",""),"bool","Error: Cannot set permissions on file/directory.\n"),
  array("size",1,array("ftp_size","!conn",""),"string","Error: Cannot read file size.\n"),
  array("help",1,array("helpFunc","!conn","status"),"string","Error: Cannot read file size.\n"),
  array("verb",1,array("verbSet","!conn","status"),"bool","Error: cannot set verbose.\n"),
  array("ls",1,array("ftp_rawlist","!conn",".",FALSE),"array","Error: cannot list directory.\n")
);
function verbFunc($arg1,$arg2){
  global $verbose;
  if($verbose == "on"){
    if(is_array($arg1)){
      echo "Description: $arg2\nVerbose: Dump Array\n=>".var_dump($arg1)."\n";
    }elseif(is_string($arg1)){
      echo "Description: $arg2\nVerbose: Dump String\n=>$arg1\n";
    }
  }
  return true;
}

function verbSet($n,$arg1){
  global $verbose;
  switch($arg1) {
    case "on":
      $verbose = "on";
      break;
    case "off":
      $verbose = "off";
      break;
  }
  echo "Verbose mode set to $verbose\n";
  return true;
}

function helpFunc() {
    global $helpCom;
    echo "$helpCom";
    return true;
}

function sanitize($commandF) {
  //Not implemented yet
  //Should use strtolower() to sanitize all values from $commandF[]
  $invalidStrArr = [];
  return true;
}

function catFileFunc($arg1,$arg2) {
  global $mode;
  $tmpFile = "tmp_".$arg2;
  $copy = getcwd()."/".$tmpFile;
  if(ftp_get($arg1, $tmpFile, $arg2, $mode)) {
    echo shell_exec('cat '.$copy);
    shell_exec('rm -rf '.$copy);
    echo "\n";
    return true;
  }
  else {
    echo "Error reading $arg2.";
    return false;
  }
}

function endSession() {
  global $connection;
  ftp_close($connection);
  exit("Goodbye!");
}

function chmodFunc($arg1, $arg2, $arg3) {
  $length = 4; //ftp_chmod need a 4 digits permission format, padding is needed
  $perm = str_pad($arg2,$length,"0", STR_PAD_LEFT);
  if (ftp_chmod($arg1, $perm, $arg3) !== false) {
   echo "Successfully chmoded $arg3 to $arg2\n.";
   return true;
  }
  else {
   echo "Could not set permissions on file $arg3\n.";
   return false;
  }
}

function shellExec($commandL,$arg1,$arg2,$arg3) {
  $output = "";
  $commandL = strtolower($commandL);
  $sani = sanitize(array($commandL,$arg1,$arg2,$arg3));
  if($sani == false) {
    $output = false;
  }
  elseif($commandL == "pwd") {
      $output = shell_exec('pwd');
  }
  elseif($commandL == "cat") {
      $output = shell_exec('cat '.strtolower($arg1));
  }
  elseif($commandL == "ls -la") {
      $output = shell_exec('ls -la');
  }
  elseif($commandL == "rm -rf") {
      $command1 = readline("Confirm you want to delete the local file ".getcwd()."/$arg1 permanently (Y/n): \n");
      $commandSplit1 = explode(" ",trim(strtolower($command1)));
      if($commandSplit1[0] == "Y") {
        $output = shell_exec('rm -rf '.getcwd()."/".strtolower($arg1));
      } else {
        $output = false;
      }
  }
   return $output;
}

function pasvFunc($arg1,$arg2) {
  global $pasvMode;
  if(strtolower($arg2) == "status" || strtolower($arg2) == "") {
    echo "Passive Mode set to '$pasvMode'\n";
  } elseif(strtolower($arg2) == "on") {
     echo "Passive Mode set to 'on'\n";
     ftp_pasv($arg1, true);
     $pasvMode = 'on';
  } elseif(strtolower($arg2) == "off") {
     echo "Passive Mode set to 'off'\n";
     ftp_pasv($arg1, false);
     $pasvMode = 'off';
  } else {
    echo "Passive Mode set to '$pasvMode'\n";
  }
  return true;
}

function transFunc($arg1,$arg2) {
  global $mode;
  if((strtolower($arg2) == "status" && $mode == FTP_BINARY) || strtolower($arg2) == "binary"){
    $mode = FTP_BINARY;
    echo "Transfer Mode set to BINARY\n";
  } elseif((strtolower($arg2) == "status" && $mode == FTP_ASCII) || strtolower($arg2) == "ascii"){
     $mode = FTP_ASCII;
     echo "Transfer Mode set to ASCII\n";
  } else {
    echo "Transfer mode argument invalid\n";
  }
  return true;
}

function createCommand($commandIndex, $commandArgs) {
  global $commandsArr;
  $comQuery = []; //array to query building
  $noArgsExpected = $commandsArr[$commandIndex][1];
  $commandName = $commandsArr[$commandIndex][0];
  verbFunc("Number of arguments: ".count($commandArgs)."\n","Number of argument(s) submitted");
  //validate number of arguments submitted
  if(count($commandArgs) < $noArgsExpected) {
     verbFunc("$noArgsExpected argument(s) expected for command '$commandName'\n","Number of argument(s) not meeting requirements from command array");
     return false;
  }
  else {
    foreach($commandsArr[$commandIndex][2] as $key => $comm) {
      if($key > 1) {//key 1 is the php function
        if(isset($commandArgs[$key-1]) == "" && $comm != "") {//if no argument is submitted, use default argument value
          array_push($comQuery,$comm);
        }
        elseif(isset($commandArgs[$key-1])) {
          array_push($comQuery,$commandArgs[$key-1]);
        }
      }
      else {
        array_push($comQuery,$comm);
      }
    }
     return $comQuery;
  }
}

function callback_rtn($args) {
  global $connection;
  global $commandsArr;
  $queryArr = [];
  $custErrorMsg = "";
  $returnType = "";
  $queryArr = [];
  $comFound = false;
// Find command
  foreach ($commandsArr as $key => $comm) {
    if(strtolower($comm[0]) == $args[0]) {
      $queryArr = createCommand($key, $args);
      if($queryArr == false) {return true;} //if command syntax is incorrect, do not execute command
      $custErrorMsg = $comm[4];
      $returnType = $comm[3];
      $comFound = true;
    }
  }
  verbFunc($queryArr,"queryArr array set prior FTP connector set.");
  if($comFound == false) {
    verbFunc($queryArr,"queryArr array dump when command cannot be found.");
    echo "Command not found.\nType help to see available commands.\n";
    return true;
  }
  $argsCount = count($queryArr);
  //replace '!conn' in array by the real ftp connector
  foreach($queryArr as $key1 => $comm1) {
    if($comm1 == "!conn") {
      $queryArr[$key1] = $connection;
    }
  }
  verbFunc($queryArr,"queryArr array dump with FTP connector set.");
  $output = "";

  switch ($argsCount) {
    case 1:
      $output = $queryArr[0]();
      break;
    case 2:
      $output = $queryArr[0]($queryArr[1]);
      break;
    case 3:
      $output = $queryArr[0]($queryArr[1],$queryArr[2]);
      break;
    case 4:
      $output = $queryArr[0]($queryArr[1],$queryArr[2],$queryArr[3]);
      break;
    case 5:
      $output = $queryArr[0]($queryArr[1],$queryArr[2],$queryArr[3],$queryArr[4]);
      break;
    case 6:
      $output = $queryArr[0]($queryArr[1],$queryArr[2],$queryArr[3],$queryArr[4],$queryArr[5]);
      break;
  }

  verbFunc("Command name: $queryArr[0], Return type: $returnType \n","Show return type for the function set in array commandsArr");
  if($returnType === "array") {
    foreach($output as $line1) {
      echo $line1."\n";
    }
  } elseif($returnType === "bool") {
    if($output == false) {
      echo $custErrorMsg."\n";
    }
  } elseif($returnType === "string") {
    echo $output."\n";
  } else {
    echo "An error occured\n";
  }
}
/******************** MAIN ********************/
$useSSL = 'n';
$ftp_server = "";
$uname = "";
$pwd = "";
$port = "21";

if(in_array('-help', $argv) || in_array('-h', $argv) ||  count($argv) > 4 || (count($argv) < 3) && count($argv) > 1){
  exit("Usage: php daemonz-ftp.php <username>@<host> <port> optional:<SSL>\n");
} elseif(count($argv) >= 3) {
    $userHost = explode("@", $argv[1]);
    if(count($userHost) != 2){ exit("=> username@host format is invalid\n");}
    $uname = $userHost[0];
    $ftp_server = $userHost[1];
    $port = $argv[2];
    $pwd = readline("Password: ");
    if(in_array('SSL', $argv)){$useSSL = 'y';}
} else {
    $useSSL = trim(strtolower(readline("Use SSL? (y/n): ")));
    $ftp_server = readline("Server IP: ");
    $port = trim(readline("Port(21): "));
    $uname = readline("Username: ");
    $pwd = readline("Password: ");
}

echo "\n> Daemonz-FTP client v.02\n";
echo $helpCom;

try {
  if($useSSL == 'y') {
    $connection = ftp_ssl_connect($ftp_server,$port,3600) or die("Could not connect to $ftp_server using SSL\n");
    echo "\nConnecting using SSL to $ftp_server as $uname@$ftp_server:$port ...\n";
  }
  else {
    $connection = ftp_connect($ftp_server,$port,3600) or die("Error connecting to $ftp_server\n");
    echo "\nConnecting to $ftp_server as $uname@$ftp_server:$port ...\n";
  }
  if (@ftp_login($connection, $uname, $pwd)) {
    echo "Connected as $uname\n";
    echo "Transfer mode: BINARY\n";
    echo "Passive mode: OFF\n";
  }
  else {
    echo "Couldn't connect as $uname\n";
    exit("Cannot connect to host ($ftp_server)");
  }
}
catch (Exception $e) {
  echo 'An error occured: ', $e->getMessage(), "\n";
  exit("Cannot connect to host ($ftp_server)");
}

ftp_pasv($connection,FALSE);

while(true) {
  $comFound = true;
  $output = "";
  echo "\n";
  $command = readline("\nftp> ");
  $commandSplit = explode(" ",trim($command));

  if (strlen($commandSplit[0]) != 0) {
    $output = callback_rtn($commandSplit);
  }
  else {
      echo "Type help for available commands\n";
  }
}
