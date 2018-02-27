<?
// WTP v%%VERSION
//  
// WTP is a Web FTP client.
//                                                                   
// Copyright (C) by Ferry Boender <%%EMAIL>
//                                                                   

// This program is free software; you can redistribute it and/or     
// modify it under the terms of the GNU General Public License       
// as published by the Free Software Foundation; either version 2    
// of the License, or (at your option) any later version.            
//                                                                   
// This program is distributed in the hope that it will be useful,   
// but WITHOUT ANY WARRANTY; without even the implied warranty of    
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the     
// GNU General Public License for more details.                      
//                                                                   
// You should have received a copy of the GNU General Public License 
// along with this program; if not, write to the Free Software       
// Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.         
//                                                                   
// For more information, see the COPYING file supplied with this     
// program.                                                          


// CHANGE THESE VARS ----------------------------------------------------------
//   For manual configuration: 
//
//   Remove the REF00x parts of the variable names, and change them 
//   $Hosts : A list of hosts to which WTP may connect. Seperate each host with
//            a semi-colon (';'). Every hostname is valid, as long as the server
//            on which WTP runs can resolve it (IP's, Internal hostnames, etc)
//            If $Hosts is left empty, the user can connect to any ftp server 
//            which is accessible to the host on which WTP runs.
$REF001Hosts = "localhost"; 
$REF002PreferenceDir = "/var/wtp/";
// ----------------------------------------------------------------------------

// DirList()
define ("DIRLIST_RIGHTS"   , 0);
define ("DIRLIST_LINKS"    , 1);
define ("DIRLIST_OWNUID"   , 2);
define ("DIRLIST_OWNGID"   , 3);
define ("DIRLIST_SIZE"     , 4);
define ("DIRLIST_MONTH"    , 5);
define ("DIRLIST_DAY"      , 6);
define ("DIRLIST_YEARTIME" , 7);
define ("DIRLIST_FILENAME" , 8);
define ("DIRLIST_SYMLINK"  , 9);
define ("DIRLIST_YEAR"     ,10);
define ("DIRLIST_TIMESTAMP",11);

// Error()
define ("ERR_NONE" , 0);
define ("ERR_BACK" , 1);
define ("ERR_EXIT" , 2);
define ("ERR_CLOSE", 4);

session_start();

// Import variables 
$Action        = Import ("Action"        , "GP");
$SubAction     = Import ("SubAction"     , "P");
$HostnamePort  = Import ("HostnamePort"  , "P");
$Hostname      = Import ("Hostname"      , "GPS");
$Port          = Import ("Port"          , "GPS");
$Username      = Import ("Username"      , "GPS");
$Password      = Import ("Password"      , "PS");
$RemoteDir     = Import ("RemoteDir"     , "GP");
$SortDirection = Import ("SortDirection" , "SGP"   , "asc");
$HiddenShow    = Import ("HiddenShow"    , "SG"    , 0);
$Sort          = Import ("Sort"          , "SG"    , "filename");
$RemoteFile    = Import ("RemoteFile"    , "G");
$RemoteFiles   = Import ("RemoteFiles"   , "P");
$FileSize      = Import ("FileSize"      , "G");
$DirName       = Import ("DirName"       , "GP");
$File          = Import ("File"          , "F");
$Destination   = Import ("Destination"   , "GP");

$RemoteDir = stripslashes($RemoteDir);
$RemoteFile = stripslashes($RemoteFile);
$DirName = stripslashes($DirName);
$Destination = stripslashes($Destination);

// Hostname and port specified?
if (isset($HostnamePort)) {
	$parts = explode(':',$HostnamePort);
	$Hostname = @$parts[0];
	$Port = @$parts[1];
}

if ($FtpStream = CheckLogin($Hosts, $Hostname, $Port, $Username, $Password)) {
	$_SESSION["Hostname"]      = $Hostname;
	$_SESSION["Port"]          = $Port;
	$_SESSION["Username"]      = $Username;
	$_SESSION["Password"]      = $Password;
	$_SESSION["SortDirection"] = $SortDirection;
	$_SESSION["HiddenShow"]    = $HiddenShow;
	$_SESSION["Sort"]          = $Sort;

	if (!$RemoteDir) {
		$RemoteDir = @ftp_pwd ($FtpStream);
	}
	
	// WTP expects path to end in a '/'
	if ($RemoteDir[strlen($RemoteDir)-1] != '/') {
		$RemoteDir = $RemoteDir . "/";
	}
	
	$Bookmarks = PrefsLoadBookmarks($PreferenceDir, $Hostname, $Username);
} else {
	$Action = "GetLogin";
}

// Forms with multiple actions 
if ($Action == "FilesDelMove") {
	if (isset($SubAction)) {
		switch ($SubAction) {
			case "Delete" : $Action = "FilesDel"; break;
			case "Move"   : $Action = "FilesMoveHtml"; break;
		}
	}
}

// Determine current action
switch ($Action) {
	case "GetLogin"     : GetLogin        ($Hosts, $Hostname, $Port, $Username, $RemoteDir);
	case "DirList"      : DirList         ($FtpStream, $Username, $Hostname, $RemoteDir, $Bookmarks, $Sort, $SortDirection, $HiddenShow); break;
	case "Download"     : Download        ($Hostname, $Port, $Username, $Password, $RemoteDir, $RemoteFile, $FileSize); break;
	case "DirNewHtml"   : DirNewHtml      ($RemoteDir); break;
	case "DirNew"       : DirNew          ($FtpStream, $RemoteDir, $DirName); break;
	case "UploadHtml"   : UploadHtml      ($RemoteDir); break;
	case "Upload"       : Upload          ($FtpStream, $RemoteDir, $File["tmp_name"], $File["name"], $File["size"], $File["type"]); break;
	case "FileDel"      : FileDel         ($FtpStream, $Hostname, $RemoteDir, $RemoteFile);
	                      DirList         ($FtpStream, $Username, $Hostname, $RemoteDir, $Bookmarks, $Sort, $SortDirection, $HiddenShow);  break;
	case "FilesDel"     : FilesDel        ($FtpStream, $Hostname, $RemoteDir, $RemoteFiles);
	                      DirList         ($FtpStream, $Username, $Hostname, $RemoteDir, $Bookmarks, $Sort, $SortDirection, $HiddenShow);  break;
	case "DirDel"       : DirDelRecursive ($FtpStream, $RemoteFile);
	                      DirList         ($FtpStream, $Username, $Hostname, $RemoteDir, $Bookmarks, $Sort, $SortDirection, $HiddenShow); break;
	case "FileMoveHtml" : FileMoveHtml    ($RemoteDir, $RemoteFile); break;
	case "FileMove"     : FileMove        ($FtpStream, $RemoteDir, $RemoteFile, $Destination); break;
	case "FilesMoveHtml": FilesMoveHtml   ($RemoteDir, $RemoteFiles); break;
	case "FilesMove"    : FilesMove       ($FtpStream, $RemoteDir, $RemoteFiles, $Destination);
	                      DirList         ($FtpStream, $Username, $Hostname, $RemoteDir, $Bookmarks, $Sort, $SortDirection, $HiddenShow);  break;
	case "BookmarkNew"  : BookmarkNew     ($FtpStream, $PreferenceDir, $Hostname, $Username, $Bookmarks, $RemoteDir);
	                      DirList         ($FtpStream, $Username, $Hostname, $RemoteDir, $Bookmarks, $Sort, $SortDirection, $HiddenShow); break;
	case "BookmarkDel"  : BookmarkDel     ($FtpStream, $PreferenceDir, $Hostname, $Username, $Bookmarks, $RemoteDir); 
	                      DirList         ($FtpStream, $Username, $Hostname, $RemoteDir, $Bookmarks, $Sort, $SortDirection, $HiddenShow); break;
	case "LogOut"       : LogOut          ();
	                      GetLogin        ($Hosts, $Hostname, $Port, $Username, ""); break;
	case "Help"         : Help            ($Username, $Hostname, $RemoteDir, $Bookmarks); break;
	default             : DirList         ($FtpStream, $Username, $Hostname, $RemoteDir, $Bookmarks, $Sort, $SortDirection, $HiddenShow); break;
}

// After actions shut down connection to ftp server
if ($FtpStream) {
	ftp_quit ($FtpStream);
}

/* Use this function to bring the value of a variable from the client
 * side/serverside into the current scope. This function tries to safely
 * implement a kind of register_globals.
 *
 * Params   : VarName  = The name of the variable to import.
 *            From     = Specifies the source from where to import the 
 *                       variable. I.e. cookies, GET, POST, etc. It should
 *                       be in the form of s string containing one or more 
 *                       of the chars in 'SPGC'. Last char in the string 
 *                       overrides previous sources.
 *            Default  = [optional] When no value is found in sources, 
 *                       assign this value.
 * Called by: -
 * Calls    : -
 * Pre      : -
 * Post     : -
 * Returns  : Contents of the variable as gotten from the last valid source
 *            described by $From. If VarName was not found in any of the 
 *            specified sources, this function returns 'undefined', and the
 *            the var to which assignment is done should also be undefined.
 * Notice   : The behaviour of an assignment from a function which doesn't 
 *            return anything is not specified I believe. Results may vary.
 */
function Import ($VarName, $From, $Default = "") {
	$i = $c = "";
	$VarValue = FALSE;

	for ($i = 0; $i < strlen($From); $i++) {
		$c = $From[$i];

		switch ($c) {
			case 'F' :
				if (array_key_exists($VarName, $_FILES)) {
					$VarValue = $_FILES[$VarName];
				}
				break;
			case 'S' :
				if (array_key_exists($VarName, $_SESSION)) {
					$VarValue = $_SESSION[$VarName];
				}
				break;
			case 'P' : 
				if (array_key_exists($VarName, $_POST)) {
					$VarValue = $_POST[$VarName]; 
				}
				break;
			case 'G' :
				if (array_key_exists($VarName, $_GET)) {
					$VarValue = $_GET[$VarName];
				}
				break;
			case 'C' : 
				if (array_key_exists($VarName, $_COOKIE)) {
					$VarValue = $_COOKIE[$VarName];
				}
				break;
			default: break;
		}
	}
	
	if ($VarValue === FALSE) {
		if ($Default != "") {
			return ($Default);
		} else {
			return; // Not defined
		}
	} else {
		return ($VarValue);
	}
}

/* Report an error through a javascript dialog and then perform some action

 * Params   : ErrorMsg = The error message to show. Should have all 
 *                       javascript special chars escaped.
 *            ErrorAct = Action to perform after the dialog's OK button has
 *                       been clicked (depends on browser). One of:
 *                       ERR_BACK (one page back in browser)
 *                       ERR_CLOSE (close the current window)
 *                       ERR_EXIT (stop the script immediatelly)
 *                       ERR_NONE (no action)
 */

function Error ($ErrorMsg, $ErrorAct) {
	?>
	<script language="javascript">alert ("<?=$ErrorMsg?>");</script>
	<?

	switch ($ErrorAct) {
		case ERR_BACK:
			?>
			<script language="javascript">
				history.go(-1);
			</script>
			<?
			break;
		case ERR_CLOSE:
			?>
			<script language="javascript">
				self.close();
			</script>
			<?
		case ERR_EXIT:
			exit();
			break;
		case ERR_NONE:
			break;
		default:
			break;
	}
}

/* Check the list of $Hosts to see if a Hostname and Port may be connect
 * to.
 * Params   : Hosts     = List of hosts to which you may connect.
 *            Hostname  = Hostname the user wants to connect to.
 *            Port      = Port the user wants to connect to.
 */
function HostIsValid ($Hosts, $Hostname, $Port) {
	if ($Port != "") {
		$HostnamePort = $Hostname.":".$Port;
	} else {
		$HostnamePort = $Hostname;
	}
	if ($Hosts == "") {
		return (TRUE); // No restrictions
	}
	
	$AllowedHosts = explode (";", $Hosts);
	if (in_array($HostnamePort, $AllowedHosts)) {
		return (TRUE);
	} else {
		return (FALSE);
	}
}

/* Presents the user with a html form in which the user can enter his login
 * information. This function is called whenever the script can't find the
 * username, password or hostname. 
 *
 * Params   : Hostname  = the hostname to prefill in the login box
 *            Username  = The username to prefill in the login box
 *            RemoteDir = The remote directory to jump to after login.
 * Called by: 
 * Calls    : CheckLogin
 * Pre      : -
 * Post     : Hostname may be set to a value
 *            Username may be set to a value
 *            Password may be set to a value
 */
function GetLogin($Hosts, $Hostname, $Port, $Username, $RemoteDir="") {
	HtmlHeader ("Login");
	?>
	<br><br>
	<center>

	<a href="%%HOMEPAGE">
	<font size="3" face="arial black" color="#000000">
		WTP %%VERSION
	</font>
	</a><br><br>
	
	<table bgcolor="#000000" border="0" cellspacing="1" cellpadding="0">
	<tr><td>
	<table bgcolor="DDDDEE" cellpadding="4" cellspacing="4" border="0">
		<tr bgcolor="#004f9e"><td colspan="2"><font color="#82c0ff"><b>Log in</b></font></td></tr>
		<form name="GetLogin" method="post">
		<input type="hidden" name="Action" value="DirList">
		<input type="hidden" name="RemoteDir" value="<?=htmlspecialchars($RemoteDir)?>">
		<tr><td>Hostname : </td><td><?
			// Show one out of 3 different possible Hostname entries 
			if ($Hosts == "") {
				?><input class="login" type="text" name="Hostname" value="<?=$Hostname?>"><?
				?>&nbsp;Port: <input size="5" type="text" name="Port" value="<?=$Port?>"><?
			} else
			if (strchr($Hosts, ';') != "") {
				// Build a dropdown list of allowed hosts
				?><select class="login" name="HostnamePort"><?
				$AllowedHosts = explode (";", $Hosts);
				foreach ($AllowedHosts as $AllowedHost) {
					if ($AllowedHost == $Hostname) {
						$Selected = "SELECTED";
					} else {
						$Selected = "";
					}
					?><option value="<?=$AllowedHost?>" <?=$Selected?>><?=$AllowedHost?></option><?
				}
				?></select><?
			} else {
				?>
				<input type="hidden" name="HostnamePort" value="<?=$Hosts?>">
				<?=$Hosts?>
				<?
			}
		?></td></tr>
		<tr><td>Username : </td><td><input class="login" type="text" name="Username" value="<?=$Username?>"></td></tr>
		<tr><td>Password : </td><td><input class="login" type="password" name="Password"></td></tr>
		<tr><td>&nbsp;     </td><td><input class="login" type="submit" value="login"></td></tr>
	</form>
	</table>
	</tr></td></table><br>
	<a href="%%HOMEPAGE">WTP</a>, &copy; 2002-2003, <a href="%%MHOMEPAGE">Ferry Boender</a> under <a href="http://www.gnu.org/copyleft/gpl.html">GPL</a>
	</center>
	<script language="javascript">
		document.GetLogin.Username.focus();
	</script>
	<?
	exit();
}

/* 
 *
 * Params   : Hosts = list of hosts to which this script may connect
 *            Hostname, Port, Username, Password: User supplied info
 *            gotten from form in GetLogin
 * Called by: GetLogin
 * Calls    : HostIsValid
 * Pre      : -
 * Post     : FtpStream is a valid connection to the FTP server 
 *            described by Hostname + Port. Username has been logged
 *            in with $Password.
 */
function CheckLogin ($Hosts, $Hostname, $Port, $Username, $Password) {
	if (isset($Hostname) && !HostIsValid($Hosts, $Hostname, $Port))  {
		Error ("You may not connect to this host.", ERR_NONE);
		return (FALSE);
	}

	if ($Hostname == "" || $Username == "" || $Password == "") {
		return (FALSE);
	}

	if (!($FtpStream = @ftp_connect ($Hostname, $Port))) {
		if ($Port == "") {
			$Port = "21"; // Default port
		}
		Error ("Could not connect to ftp server at ".$Hostname.", port ".$Port, ERR_NONE);
		return (FALSE);
	}
	
	if (!($FtpLogin = @ftp_login ($FtpStream, $Username, $Password))) {
		Error ("Login failed. (Bad usename/password?)", ERR_NONE);
		return (FALSE);
	}

	return ($FtpStream);
}

/* Logs out the user by removing the cookies with the connection information
 *
 * Params   : -
 * Called by: DirList
 * Calls    : GetLogin
 * Pre      : -
 * Post     : The cookies with userinformation have been removed
 */
function LogOut() {
	session_destroy();
}


function DirListSortSize ($a, $b) {
	global $SortDirectionInternal;

	// Use first char of rights to check if both are files or dirs 
	if ($a[DIRLIST_RIGHTS][0] == $b[DIRLIST_RIGHTS][0]) {
		if ($a[DIRLIST_SIZE] > $b[DIRLIST_SIZE]) {
			return ($SortDirectionInternal);
		} else
		if ($a[DIRLIST_SIZE] < $b[DIRLIST_SIZE]) {
			return (-1 * $SortDirectionInternal);
		}

		return (0);
	} else {
		// Directories always go to the top 
		return ($a[DIRLIST_RIGHTS][0] == 'd' ? -1 : 1);
	}
}

function DirListSortDate ($a, $b) {
	global $SortDirectionInternal;

	// Use first char of rights to check if both are files or dirs 
	if ($a[DIRLIST_RIGHTS][0] == $b[DIRLIST_RIGHTS][0]) {
		if ($a[DIRLIST_TIMESTAMP] > $b[DIRLIST_TIMESTAMP]) {
			return ($SortDirectionInternal);
		} else
		if ($a[DIRLIST_TIMESTAMP] < $b[DIRLIST_TIMESTAMP]) {
			return (-1 * $SortDirectionInternal);
		}

		return (0);
	} else {
		// Directories always go to the top
		return ($a[DIRLIST_RIGHTS][0] == 'd' ? -1 : 1);
	}
}

function DirListSortFilename ($a, $b) {
	global $SortDirectionInternal;

	// Use first char of rights to check if both are files or dirs 
	if ($a[DIRLIST_RIGHTS][0] == $b[DIRLIST_RIGHTS][0]) {
		return (strcasecmp($a[DIRLIST_FILENAME], $b[DIRLIST_FILENAME])*$SortDirectionInternal);
	} else {
		// Directories always go to the top
		return ($a[DIRLIST_RIGHTS][0] == 'd' ? -1 : 1);
	}
}


/* Creates a html table with a directory listing of the RemoteDir directory. 
 * The dir listing contains links to files so they can be downloaded and to
 * directories so they can be entered
 *
 * Params   : FtpStream = A pointer to an opened FTP stream
 *            Username = The username with which wtp is currently connected
 *            RemoteDir = The directory for which to generate a listing
 * Called by: DirList, CheckLogin
 * Calls    : DirList, FileDownload
 * Pre      : A stream to a ftp server should be openend
 * Post     : A html table with a dir listing is generated
 */
function DirList($FtpStream, $Username, $Hostname, $RemoteDir, $Bookmarks, $Sort, $SortDirection, $HiddenShow) {
	global $SortDirectionInternal;
	
	// Confirmation javascript code
	?>
	<script>
		function ConfirmDelete () {
			return(confirm ("This will delete this file or directory and any underlaying files/directories. Are you sure you want to do this?"));
		}
	</script>
	<?
	@$Result = @ftp_chdir ($FtpStream, $RemoteDir);
	if ($Result == true) {
		$RemoteFiles = @ftp_rawlist ($FtpStream, "-a");
		if (!$RemoteFiles) {
			Error ("Couldn't retrieve directory listing.", ERR_NONE);
		}

		$NrOfFiles = 0;
		
		// Parse the rawlist we recieved into a nice array
		for ($i = 0; $i < count($RemoteFiles); $i++ ) {
			$RemoteFileInformation = explode (" ",$RemoteFiles[$i]);
			$ParsedFileInformation = array();
	
			if (count($RemoteFileInformation) <= 10) {
				continue;
			}
			// Create a new array without empty fields caused by explode
			$SymlinkPos = -1;
			$NonEmptyParts = 0;
			for ($j = 0; $j < count($RemoteFileInformation); $j++ ) {
				if ($RemoteFileInformation[$j] != "") {
					$ParsedFileInformation[$NonEmptyParts++] = $RemoteFileInformation[$j];
					if ($RemoteFileInformation[$j] == "->") {
						$SymlinkPos = $NonEmptyParts-1;
					}
				}
			}
			if ($SymlinkPos == -1) {
				$SymlinkPos = count($ParsedFileInformation);
			}
	
			// Get the full filename
			$FullFilename = "";
			for ($j = DIRLIST_FILENAME; $j < $SymlinkPos; $j++) {
				$FullFilename = $FullFilename . $ParsedFileInformation[$j]. " ";
			}
			$ParsedFileInformation[DIRLIST_FILENAME] = trim($FullFilename);
	
			// Get the full symlink (if present)
			$FullSymlink = "";
			for ($j = $SymlinkPos+1; $j < count($ParsedFileInformation); $j++) {
				$FullSymlink = $FullSymlink . $ParsedFileInformation[$j] . " ";
			}
			$ParsedFileInformation[DIRLIST_SYMLINK] = trim($FullSymlink);
			
			// Get the full timestamp for this file
			$TimeNow = time();
			if (strchr($ParsedFileInformation[DIRLIST_YEARTIME], ":")) {
				// Year unknown, time known
				$TimeFile = strtotime ($ParsedFileInformation[DIRLIST_MONTH]." ".$ParsedFileInformation[DIRLIST_DAY]." ".date("Y", $TimeNow)." ".$ParsedFileInformation[DIRLIST_YEARTIME]);
				if ($TimeFile > $TimeNow) {
					// Subtract 1 year
					$TimeFile = strtotime ($ParsedFileInformation[DIRLIST_MONTH]." ".$ParsedFileInformation[DIRLIST_DAY]." ".date("Y", $TimeNow)." ".$ParsedFileInformation[DIRLIST_YEARTIME]. " 1 year ago");
				}
			} else {
				// Year known, time unknown
				$TimeFile = strtotime ($ParsedFileInformation[DIRLIST_MONTH]." ".$ParsedFileInformation[DIRLIST_DAY]." ".$ParsedFileInformation[DIRLIST_YEARTIME]);
			}
			$ParsedFileInformation[DIRLIST_TIMESTAMP] = $TimeFile;
			
			$ParsedRemoteFiles[$NrOfFiles++] = $ParsedFileInformation;
		}
	}
	
	// Sort
	
	$SortIconSize = "";
	$SortIconDate = "";
	$SortIconFilename = "";
	$SortDirectionSize = "asc";
	$SortDirectionDate = "asc";
	$SortDirectionFilename = "asc";
	
	// Set sort direction for usort sorting functions
	if ($SortDirection == "asc") {
		$SortDirectionInternal = 1;
	} else {
		$SortDirectionInternal = -1;
	}

	switch ($Sort) {
		case "size" : 
			if (is_array($ParsedRemoteFiles)) {
				usort ($ParsedRemoteFiles, "DirListSortSize"); 
			}
			$SortIconSize = "<img src=\"images/sort_".$SortDirection.".gif\" border=\"0\">"; 
			$SortDirectionSize = ($SortDirection == "desc" ? "asc" : "desc");
			break;
		case "date" : 
			if (is_array($ParsedRemoteFiles)) {
				usort ($ParsedRemoteFiles, "DirListSortDate"); 
			}
			$SortIconDate = "<img src=\"images/sort_".$SortDirection.".gif\" border=\"0\">";
			$SortDirectionDate = ($SortDirection == "desc" ? "asc" : "desc");
			break;
		case "filename" : 
			if (is_array($ParsedRemoteFiles)) {
				usort ($ParsedRemoteFiles, "DirListSortFilename"); 
			}
			$SortIconFilename = "<img src=\"images/sort_".$SortDirection.".gif\" border=\"0\">"; 
			$SortDirectionFilename = ($SortDirection == "desc" ? "asc" : "desc");
			break;
		default: 
			break;
	}

	// Put the files in a table
	HtmlHeader ("Dir Listing");
	UserInterfaceHeader($Username, $Hostname, $RemoteDir, $Bookmarks);
	?>
	
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
	<form target="<?=$_SERVER['PHP_SELF']?>" method="POST">
	<tr bgcolor="#004f9e">
		<?
		?>
		<td><font color="#82c0ff"><b>Rights</b></font></td>
		<td><font color="#82c0ff"><b>UID</b></font></td>
		<td><font color="#82c0ff"><b>GID</b></font></td>
		<td align="right"><b><a class="lnk_sort" href="wtp.php?Action=DirList&RemoteDir=<?=urlencode($RemoteDir)?>&Sort=size&SortDirection=<?=$SortDirectionSize?>">Size <?=$SortIconSize?></a></b> &nbsp;</td>
		<td><b><a class="lnk_sort" href="wtp.php?Action=DirList&RemoteDir=<?=urlencode($RemoteDir)?>&Sort=date&SortDirection=<?=$SortDirectionDate?>">Date <?=$SortIconDate?></a></b></td>
		<td><font color="#82c0ff">&nbsp;</font></td>
		<td><font color="#82c0ff">&nbsp;</font></td>
		<td><b><a class="lnk_sort" href="wtp.php?Action=DirList&RemoteDir=<?=urlencode($RemoteDir)?>&Sort=filename&SortDirection=<?=$SortDirectionFilename?>">Filename <?=$SortIconFilename?></a></b></td>
		<td><font color="#82c0ff"><b>Symlinks to</b></font></td>
	</tr>
	<?
		$DirUp = StripLastDir ($RemoteDir);
	?>
	<tr bgcolor="#DDDDEE">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td><a href="wtp.php?Action=DirList&RemoteDir=<?=urlencode($DirUp)?>"><font color="#0000FF"><b>..</b></font></a></td>
		<td>&nbsp;</td>
	</tr>
	<?
	$RowColor = "#EEEEFF";
	
	if ($Result == true) {
		$TimeNow = time();
		
		for ($i = 0; $i < count($ParsedRemoteFiles); $i++ ) {
			// This weird php tag stuff is because it'll save room in the html
			// code.

			// Skip all parsed info which doesn't contain more than 10 columns
			// because they are probably not file information lines. I'm just
			// guessing here, so I really should just read the RFC's.
			if (
				count($ParsedRemoteFiles[$i]) > 10 && 
				($ParsedRemoteFiles[$i][DIRLIST_FILENAME][0] != '.' || $HiddenShow == 1) && 
				($ParsedRemoteFiles[$i][DIRLIST_FILENAME] != "." && $ParsedRemoteFiles[$i][DIRLIST_FILENAME] != "..")
				) 
			{

				?><tr bgcolor="<?=$RowColor?>"><?
				?><td><?=$ParsedRemoteFiles[$i][DIRLIST_RIGHTS]?></td><?
				?><td><?=$ParsedRemoteFiles[$i][DIRLIST_OWNUID]?></td><?
				?><td><?=$ParsedRemoteFiles[$i][DIRLIST_OWNGID]?></td><?
				?><td align="right"><?=number_format($ParsedRemoteFiles[$i][DIRLIST_SIZE])?> &nbsp; </td><?
				?><td><?=date("d M Y H:i", $ParsedRemoteFiles[$i][DIRLIST_TIMESTAMP])?></td><?
				?></td><?
				?><td><?
					if ($ParsedRemoteFiles[$i][0][0] == 'd' || ($ParsedRemoteFiles[$i][0][0] == 'l' && ftp_isdir($FtpStream, $RemoteDir.$ParsedRemoteFiles[$i][DIRLIST_FILENAME])) ) {
						// Nothing
					} else {
						// File actions
						?><input type="checkbox" name="RemoteFiles[]" value="<?=htmlentities($ParsedRemoteFiles[$i][DIRLIST_FILENAME])?>"><?
					}
					
				?></td><?
				?><td><?
					if ($ParsedRemoteFiles[$i][0][0] == 'd' || ($ParsedRemoteFiles[$i][0][0] == 'l' && ftp_isdir($FtpStream, $RemoteDir.$ParsedRemoteFiles[$i][DIRLIST_FILENAME])) ) {
						// Directory actions
						?><a title="Delete directory and contents" href="wtp.php?Action=DirDel&RemoteDir=<?=urlencode($RemoteDir)?>&RemoteFile=<?=urlencode($RemoteDir)?><?=urlencode($ParsedRemoteFiles[$i][DIRLIST_FILENAME])?>" OnMouseOver="window.status='Delete dir'; return true" OnMouseOut="window.status=''; return true"><img src="images/dirdel.gif" border="0" alt="Delete" OnClick="return(ConfirmDelete())"></a>&nbsp;<?
						?><a title="Move directory and contents" href="javascript:WinOpen('FileMoveHtml','<?=rawurlencode(rawurlencode($RemoteDir))?>&RemoteFile=<?=rawurlencode(rawurlencode($ParsedRemoteFiles[$i][DIRLIST_FILENAME]))?>')" OnMouseOver="window.status='Move dir'; return true" OnMouseOut="window.status=''; return true"><img src="images/dirmove.gif" border="0" alt="Move"></a>&nbsp;<?
					} else {
						// File actions
						?><a title="Delete file" href="wtp.php?Action=FileDel&RemoteDir=<?=urlencode($RemoteDir)?>&RemoteFile=<?=urlencode($RemoteDir)?><?=urlencode($ParsedRemoteFiles[$i][DIRLIST_FILENAME])?>" OnMouseOver="window.status='Delete file'; return true" OnMouseOut="window.status=''; return true"><img src="images/filedel.gif" border="0" alt="Delete" OnClick="return(ConfirmDelete())"></a>&nbsp;<?
						?><a title="Move file" href="javascript:WinOpen('FileMoveHtml','<?=rawurlencode(rawurlencode($RemoteDir))?>&RemoteFile=<?=rawurlencode(rawurlencode($ParsedRemoteFiles[$i][DIRLIST_FILENAME]))?>')" OnMouseOver="window.status='Move file'; return true" OnMouseOut="window.status=''; return true"><img src="images/filemove.gif" border="0" alt="Move"></a>&nbsp;<?
					}
					
				?></td><?
				?><td><?
					if ($ParsedRemoteFiles[$i][0][0] == 'd' || ($ParsedRemoteFiles[$i][0][0] == 'l' && ftp_isdir($FtpStream, $RemoteDir.$ParsedRemoteFiles[$i][DIRLIST_FILENAME])) ) {
						// Dir display
						?><a href="wtp.php?Action=DirList&RemoteDir=<?=urlencode($RemoteDir)?><?=urlencode($ParsedRemoteFiles[$i][DIRLIST_FILENAME])?>/"><font color="#0000FF"><?
					} else {
						// File display
						?><a href="wtp.php?Action=Download&RemoteFile=<?=urlencode($ParsedRemoteFiles[$i][DIRLIST_FILENAME])?>&RemoteDir=<?=urlencode($RemoteDir)?>&FileSize=<?=$ParsedRemoteFiles[$i][DIRLIST_SIZE]?>"><font color="#000000"><?
					}
					
					?><?=htmlentities($ParsedRemoteFiles[$i][DIRLIST_FILENAME])?><?
					?></font></a><?
					
				?></td><?
				?><td><?=htmlentities($ParsedRemoteFiles[$i][DIRLIST_SYMLINK])?></td><?
				?></tr>
				<?
				if ($RowColor == "#EEEEFF") {
					$RowColor = "#DDDDEE";
				} else {
					$RowColor = "#EEEEFF";
				}
			}
		}
	} else {
		?>
		<tr bgcolor="<?=$RowColor?>">
		<td colspan="9">
		<font color="#FF0000">File not found</font> or
		<font color="#FF0000">Permission denied</font>
		</td>
		</tr>
		<?
	}
	?>
	</table>
	<br>
	<input type="hidden" name="RemoteDir" value="<?=htmlspecialchars($RemoteDir)?>">
	<input type="hidden" name="Action" value="FilesDelMove">
	<table border="0" cellspacing="2" cellpadding="3">
		<tr>
		<td>
			<input type="submit" name="SubAction" value="Delete">
		</td>
		<td>
		&nbsp;
		</td>
		<td>
			<input type="submit" name="SubAction" value="Move">
		</td>
		</tr>
	</table>
	</form>
	<?
	exit();
}

/* Creates a html header. 
 *
 * Params   : PageTitle = The title of the html page
 * Called by: -
 * Calls    : -
 * Pre      : -
 * Post     : A html header is generated
 */
function HtmlHeader ($PageTitle) {
	?>
	<html>
	<head>
		<title><?=$PageTitle?></title>
		<style>
			body         { font-family: verdana; font-size: 12px; }
			td           { font-family: fixed, courier new, courier; font-size: 12px; }
			select.login { font-family: fixed, courier new, courier; font-size: 12px; width: 150px;}
			input.login  { font-family: fixed, courier new, courier; font-size: 12px; width: 150px;}
			select       { font-family: fixed, courier new, courier; font-size: 12px;}
			input        { font-family: fixed, courier new, courier; font-size: 12px;}
			a:link       { font-family: fixed, courier new, courier; color: #0000FF; font-size: 12px; text-decoration: none;}
			a:visited    { font-family: fixed, courier new, courier; color: #0000FF; font-size: 12px; text-decoration: none;}
			a.lnk_sort   { font-family: fixed, courier new, courier; color: #D0D0FF; font-size: 12px; text-decoration: none;}
		</style>
		<script language="javascript">
			function WinOpen(Action, RemoteDir) {
				window.open("wtp.php?Action="+Action+"&RemoteDir="+RemoteDir,"WtpPopUp","height=120,width=430,directories=no,location=no,menubar=no,status=yes,toolbar=no,personalbar=no,resizable=yes,scrollbars=yes"); 
			}
		</script>
	</head>
	<body topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" marginheight="0" marginwidth="0">
	<?
}

/* Outputs HTML code for the toolbar at the top of the screen of the directory 
 * listing interface. 
 *
 * Params   : Username = The username with which wtp is currently connected
 *            Hostname  = The hostname to which wtp currently is connected
 *            RemoteDir = The current dir being viewed
 *            ShowRemoteDir = 1 = Show the current remote dir, 0 = don't show. Default1
 *                            
 * Called by: DirList
 * Calls    : -
 * Pre      : -
 * Post     : Toolbar code is sent to output
 */
function UserInterfaceHeader ($Username, $Hostname, $RemoteDir, $Bookmarks, $ShowRemoteDir=1) {
	global $HiddenShow;

	?>
	<table background="images/toolbar.gif" width="100%" height="34" cellspacing="0" cellpadding="0">
	<tr>
	<td align="left"> &nbsp; 
		<font size="3"><b>WTP</b></font> &nbsp;
		<a title="Directory up" href="wtp.php?Action=DirList&RemoteDir=<?=urlencode(StripLastDir($RemoteDir))?>" OnMouseOver="window.status='Directory Up'; return true" OnMouseOut="window.status=''; return true"><img src="images/dirup.gif" border="0" alt="Directory Up"></a>
		<a title="Refresh" href="wtp.php?Action=DirList&RemoteDir=<?=urlencode($RemoteDir)?>" OnMouseOver="window.status='Refresh'; return true" OnMouseOut="window.status=''; return true"><img src="images/refresh.gif" border="0" alt="Refresh"></a>
		<a title="Show/Hide hidden dirs/files" href="wtp.php?Action=DirList&RemoteDir=<?=urlencode($RemoteDir)?>&HiddenShow=<?=($HiddenShow ^ 1)?>" OnMouseOver="window.status='Show/Hide hidden files'; return true" OnMouseOut="window.status=''; return true"><img src="images/dirhidden.gif" border="0" alt="Show/Hide hidden dirs/files"></a>
		<a title="Create new directory" href="javascript:WinOpen('DirNewHtml','<?=rawurlencode(rawurlencode($RemoteDir))?>')" OnMouseOver="window.status='Create new directory'; return true" OnMouseOut="window.status=''; return true"><img src="images/dirnew.gif" border="0" alt="Create Directory"></a>
		<a title="Go to home directory" href="wtp.php?Action=DirList&RemoteDir=" OnMouseOver="window.status='Go to home directory'; return true" OnMouseOut="window.status='Go to home directory'; return true"><img src="images/dirhome.gif" border="0" alt="Go to home directory"></a>
		<a title="Upload a file" href="javascript:WinOpen('UploadHtml','<?=rawurlencode(rawurlencode($RemoteDir))?>')" OnMouseOver="window.status='Upload a file'; return true" OnMouseOut="window.status=''; return true"><img src="images/upload.gif" border="0" alt="Upload file"></a>
		<a title="Log out" href="wtp.php?Action=LogOut" OnMouseOver="window.status='Log out'; return true" OnMouseOut="window.status=''; return true"><img src="images/logout.gif" border="0" alt="Logout"></a>
		<a title="Help" href="wtp.php?Action=Help&RemoteDir=<?=urlencode($RemoteDir)?>" OnMouseOver="window.status='Help'; return true" OnMouseOut="window.status=''; return true"><img src="images/help.gif" border="0" alt="Help"></a>
	</td>
	<?
	// Bookmarks
	if ($Bookmarks) {
		?>
		<form>
		<td align="right">
			<input type="hidden" name="Action" value="DirList">
			<select name="RemoteDir" OnChange="this.form.submit();">
				<?
				for ($i = 0; $i < count($Bookmarks); $i++) {
					?><option value="<?=htmlspecialchars($Bookmarks[$i])?>" 
					<? // Default selected
					if ($Bookmarks[$i] == $RemoteDir) {
						print ("selected");
					}
					?>> <?=htmlspecialchars($Bookmarks[$i])?><?
				}
				?>
			</select>
			<input type="submit" value="Go">
		</td>
		</form>
		<?
	}
	?>
	<form>
	<td align="right">
		<input type="hidden" name="Action" value="DirList">
		<input type="text" name="RemoteDir" value="<?=htmlspecialchars($RemoteDir)?>" size="40">
		<input type="submit" value="Change dir">
		<?
		// Show bookmark modifiers
		if (!@in_array($RemoteDir, $Bookmarks)) {
			?>
			<a title="Bookmark current directory" href="wtp.php?Action=BookmarkNew&RemoteDir=<?=urlencode($RemoteDir)?>" OnMouseOver="window.status='Bookmark current dir'; return true" OnMouseOut="window.status=''; return true;">
			<img src="images/bookmarknew.gif" alt="New Bookmark" border="0">
			</a>
			<?
		} else {
			?>
			<a title="Remove current dir from bookmarks" href="wtp.php?Action=BookmarkDel&RemoteDir=<?=urlencode($RemoteDir)?>" OnMouseOver="window.status='Remove current dir from bookmarks'; return true" OnMouseOut="window.status=''; return true">
			<img src="images/bookmarkdel.gif" alt="Delete Bookmark" border="0">
			</a>
			<?
		}
		?>
		&nbsp;
	</td>
	</form>
	</tr>
	</table>

	<br>
	<?
		if ($ShowRemoteDir == 1) {
			$Dirs = explode ("/",$RemoteDir);
			$FullPath = "/";
			$FullPathWithUrls = "<a href=\"wtp.php?Action=DirList&RemoteDir=/\">/</a>";
			foreach ($Dirs as $Dir) {
				if ($Dir != "") {
					$FullPath .= $Dir."/";
					$FullPathWithUrls .= "<a href=\"wtp.php?Action=DirList&RemoteDir=".urlencode($FullPath)."\">".htmlspecialchars($Dir)."</a>/";
				}
			}
			?>
			&nbsp; <b>Current Dir:</b>
			<?=$Username?>@<?=$Hostname?>:<?=$FullPathWithUrls?>
			<br><br>
			<?
		}
}

/* Removes the last dir from a path. If the path is the root dir, nothing is
 * done.
 *
 * Params   : Path = The path to remove the last dir from
 * Called by: DirList
 * Calls    : -
 * Pre      : -
 * Post     : -
 * Returns  : Path with the last dir removed. If there was no last dir, it
 *            returns '/'
 */
function StripLastDir ($Path) {
	if ($Path[strlen($Path)-1] == '/') {
		$Path[strlen($Path)-1] = ' ';
	}
	$Path =  substr($Path, 0, strrpos($Path, '/')+1);
	if ($Path[0] != '/') {
		$Path = "/";
	}
	return $Path;
}

/* Reads a file from the ftp server and outputs it to the client browser
 *
 * Params   : Hostname = The hostname to which wtp currently is connected
 *            Username = The username with which to connect
 *            Password = The password with which to connect
 *            RemoteDir = The remote current working directory
 *            RemoteFile = The file on the ftp to send to the client.
 * Called by: DirList
 * Calls    : -
 * Pre      : -
 * Post     : The file is sent to the standaard output 
 */
function Download ($Hostname, $Port, $Username, $Password, $RemoteDir, $RemoteFile,
	$FileSize) {

	$f = @fopen ("ftp://".$Username.":".$Password."@".$Hostname.":".$Port.$RemoteDir.$RemoteFile, "rb");
	if ($f === FALSE) {
		HtmlHeader ("Download");
		Error ("Couldn't open file ".$RemoteFile, ERR_BACK);
		exit();
	} else {
		header ("Content-Type: application/octet-stream; name=\"$RemoteFile\"");
		header ("Content-Length: $FileSize");
		header ("Content-Transfer-Coding: binary");
		header ("Connection: close");
		header ("Content-Disposition: attachment; filename=\"$RemoteFile\"");
			
		$result = fpassthru($f);
		if ($result === FALSE) {
			// Should display some error, but we can't do anything with it 
			exit(); // Ouch 
		}
	}
}

/* Outputs HTML code for creating a new directory
 *
 * Params   : RemoteDir = The current remote directory
 * Called by: DirList
 * Calls    : DirNew
 * Pre      : -
 * Post     : The information is sent to DirNew which will create the dir
 */
function DirNewHtml($RemoteDir) { 
	HtmlHeader ("Make dir");
	?>
	<br>
	<center>
	<b>Enter the name of the directory to create:</b><br><br>
	<form name="newdir" method="post">
		<input type="hidden" name="Action" value="DirNew">
		<input type="hidden" name="RemoteDir" value="<?=$RemoteDir?>">
		&nbsp; <input type="text" name="DirName" value="<?=htmlspecialchars($RemoteDir)?>" size="45">
		<input type="submit" value="Make Dir">
	</form>
	<br>
	</center>
	<script language="javascript">
		document.newdir.dirname.focus();
	</script>
	<?
}

/* Creates a new directory
 *
 * Params   : FtpStream = A opened stream to the ftp server
 *            RemoteDir = The current remote working directory
 *            DirName   = The name of the dir to create
 * Called by: DirNewHtml
 * Calls    : -
 * Pre      : A stream to the ftp server should be open.
 * Post     : The directory is created and the current window closed
 */
function DirNew ($FtpStream, $RemoteDir, $DirName) {
	$result = @ftp_mkdir ($FtpStream, $DirName);
	?>
	<html>
	<head>
	</head>
	<body>
		<?
			if ($result === FALSE) {
				Error ("Couldn't create directory ".$DirName, ERR_CLOSE);
			} else {
				?>
				<script>
				opener.location = "wtp.php?Action=DirList&RemoteDir=<?=htmlspecialchars(rawurlencode($DirName))?>";
				self.close();
				</script>
				<?
			}
		?>
	</body>
	</html>
	<?
}

/* Shows HTML code for a file upload
 *
 * Params   : RemoteDir = The current remote working dir in which to upload
 * Called by: DirList
 * Calls    : Upload
 * Pre      : -
 * Post     : The gathered information is sent to Upload()
 */
function UploadHtml($RemoteDir) { 
	HtmlHeader ("Upload file");
	?>
	<br>
	<center>
	<b>Select a file to upload:</b><br><br>
	<form name="upload" method="post" ENCTYPE="multipart/form-data" >
		<input type="hidden" name="Action" value="Upload">
		<input type="hidden" name="RemoteDir" value="<?=htmlentities($RemoteDir)?>">
		&nbsp; <input type="file" name="File" value="">
		<input type="submit" value="Upload">
	</form>
	<br>
	</center>
	<script language="javascript">
		document.upload.File.focus();
	</script>
	<?
}

/* Handles an uploaded file
 *
 * Params   : FtpStream = An open stream to an ftp server
 *            RemoteDir = The remote curent working dir in which to place upload
 *            File     = Path to the uploaded file
 *            File_name = Original name of uploaded file
 *            File_size = Size of the uploaded file
 *            File_type = The Mime type of the uploaded file
 * Called by: UploadHtml
 * Calls    : -
 * Pre      : -
 * Post     : The uploaded file is moved from File to RemoteDir and
 *            the uploaded file is renamed to File_name
 */
function Upload ($FtpStream, $RemoteDir,$File,  $File_name, $File_size, 
                 $File_type) {
	$result = @ftp_put ($FtpStream, $RemoteDir.$File_name, $File, FTP_BINARY);
	?>
	<html>
	<head>
	</head>
	<body>
		<?
			if ($result === FALSE) {
				Error ("Couldn't upload file ".$File_name, ERR_CLOSE);
			} else {
				?>
				<script>
				opener.location = 'wtp.php?Action=DirList&RemoteDir=<?=rawurlencode($RemoteDir)?>';
				self.close();
				</script>
				<?
			}
		?>
	</body>
	</html>
	<?
}

/* Deletes a remote file on the ftp server
 *
 * Params   : FtpStream = An open stream to an ftp server
 *            Hostname = The hostname of the ftp server 
 *            RemoteDir = The remote curent working dir in which to place upload
 *            RemoteFile = Path and filename of file to delete
 * Called by: DirList
 * Calls    : DirList
 * Pre      : -
 * Post     : The file is deleted or an error dialog is shown
 */
function FileDel($FtpStream, $Hostname, $RemoteDir, $RemoteFile) {
	$result = @ftp_delete ($FtpStream, $RemoteFile);

	?>
	<html>
	<head>
	</head>
	<body>
		<?
			if ($result === FALSE) {
				Error ("Couldn't delete file ".$RemoteFile, ERR_NONE);
			} else {
				?>
				<script>
				opener.location = 'wtp.php?Action=DirList&RemoteDir=<?=rawurlencode($RemoteDir)?>';
				self.close();
				</script>
				<?
			}
		?>
	</body>
	</html>
	<?
}

function FilesDel ($FtpStream, $Hostname, $RemoteDir, $RemoteFiles) {
	$ErrorFiles = "";
	foreach ($RemoteFiles as $RemoteFile) {
		if (@ftp_delete ($FtpStream, $RemoteDir.$RemoteFile) == false) {
			$ErrorFiles .= "\t$RemoteFile\\n";
		}
	}
	if ($ErrorFiles != "") {
		// Does not use Error() function cause I'm too lazy to make Error() withstand weird chars 
		?>
		<script language="javascript">
			alert ("Couldn't delete the following files: \n\n<?=$ErrorFiles?>\n\nCheck permissions.");
		</script>
		<?
	}
}
function FileMoveHtml ($RemoteDir, $RemoteFile) {
	HtmlHeader ("Move/Rename file");
	?>
	<br>
	<center>
	<b>Enter destination path or new filename:</b><br><br>
	<form method="post">
		<input type="hidden" name="Action" value="FileMove">
		<input type="hidden" name="RemoteDir" value="<?=htmlspecialchars($RemoteDir)?>">
		<input type="hidden" name="RemoteFile" value="<?=htmlspecialchars($RemoteFile)?>">
		<input type="text" name="Destination" size="35">
		<input type="submit" value="Move / Rename">
	</form>
	</center>
	<?
}

function FileMove ($FtpStream, $RemoteDir, $RemoteFile, $Destination) {
	$ErrorFiles = "";
	if ($Destination[0] != "/") { // Relative rename / move 
		if ($Destination[strlen($Destination)-1] != "/") { // Rename file 
			if (@ftp_rename ($FtpStream, $RemoteDir.$RemoteFile, $RemoteDir.$Destination) == false) {
				$ErrorFiles .= $RemoteFile;
			}
		} else { // Move file 
			if (@ftp_rename ($FtpStream, $RemoteDir.$RemoteFile, $RemoteDir.$Destination.$RemoteFile) == false) {
				$ErrorFiles .= $RemoteFile;
			}
		}
	} else { // Absolute move
		if ($Destination[strlen($Destination)-1] != "/") { // Move & Rename
			if (@ftp_rename ($FtpStream, $RemoteDir.$RemoteFile, $Destination) == false) {
				$ErrorFiles .= $RemoteFile;
			}
		} else { // Move
			if (@ftp_rename ($FtpStream, $RemoteDir.$RemoteFile, $Destination.$RemoteFile) == false) {
				$ErrorFiles .= $RemoteFile;
			}
		}
	}
	?>
	<html>
	<head>
	</head>
	<body>
		<script>
		<?
			if ($ErrorFiles != "") {
				?>alert ("Couldn't move the following files: \n\n<?=$ErrorFiles?>\n");<?
			}
		?>
		opener.location = 'wtp.php?Action=DirList&RemoteDir=<?=htmlspecialchars(rawurlencode($RemoteDir))?>';
		self.close();
		</script>
	</body>
	</html>
	<?
}

function FilesMoveHtml ($RemoteDir, $RemoteFiles) {
	HtmlHeader ("Move files");
	?>
	<br>
	<center>
	<b>Enter destination path for files:</b><br><br>
	<form method="post">
		<input type="hidden" name="Action" value="FilesMove">
		<input type="hidden" name="RemoteDir" value="<?=htmlspecialchars($RemoteDir)?>">
		<?
			foreach ($RemoteFiles as $RemoteFile) {
				?><input type="hidden" name="RemoteFiles[]" value="<?=htmlspecialchars($RemoteFile)?>"><?
			}
		?>
		<input type="text" name="Destination" size="35">
		<input type="submit" value="Move files">
	</form>
	</center>
	<?
}

function FilesMove ($FtpStream, $RemoteDir, $RemoteFiles, $Destination) {
	$ErrorFiles = "";
	if ($Destination[0] != "/") {
		// Relative rename / move 
		if ($Destination[strlen($Destination)-1] != "/") {
			// Rename files 
			foreach ($RemoteFiles as $RemoteFile) {
				if (@ftp_rename ($FtpStream, $RemoteDir.$RemoteFile, $RemoteDir.$Destination) == false) {
					$ErrorFiles .= $RemoteFile."\n";
				}
			}
		} else {
			// Move files
			foreach ($RemoteFiles as $RemoteFile) {
				if (@ftp_rename ($FtpStream, $RemoteDir.$RemoteFile, $RemoteDir.$Destination.$RemoteFile) == false) {
					$ErrorFiles .= $RemoteFile."\n";
				}
			}
		}
	} else {
		// Absolute move
		if ($Destination[strlen($Destination)-1] != "/") {
			// Moves & Renames
			foreach ($RemoteFiles as $RemoteFile) {
				if (@ftp_rename ($FtpStream, $RemoteDir.$RemoteFile, $Destination) == false) {
					$ErrorFiles .= $RemoteFile."\n";
				}
			}
		} else {
			// Moves
			foreach ($RemoteFiles as $RemoteFile) {
				if (@ftp_rename ($FtpStream, $RemoteDir.$RemoteFile, $Destination.$RemoteFile) == false) {
					$ErrorFiles .= $RemoteFile."\n";
				}
			}
		}
	}
		
	if ($ErrorFiles != "") {
		// Does not use Error() due to possible weird chars 
		?>
		<script language="javascript">
				alert ("Couldn't move the following files: \n\n<?=htmlentities($ErrorFiles)?>\n");
		</script>
		<?
	}
}

/* Shows HTML code for a file(s) move
 * NOTE: Is this being used??
 * Params   : RemoteDir = The current remote working dir in which to upload
 * Called by: DirList
 * Calls    : Upload
 * Pre      : -
 * Post     : The gathered information is sent to Upload()
 */
function Move ($RemoteDir) { 
	HtmlHeader ("Move file(s)");
	?>
	<br>
	<center>
	<b>Enter a destination to which to move file(s):</b><br><br>
	<form method="post">
		<input type="hidden" name="Action" value="Move">
		<input type="hidden" name="RemoteDir" value="<?=$RemoteDir?>">
		&nbsp; <input type="file" name="File" value="">
		<input type="submit" value="Upload">
	</form>
	<br>
	</center>
	<?
}

/* Recursively deletes a remote dir on the ftp server
 *
 * Params   : FtpStream = An open stream to an ftp server
 *            RemoteDir = The remote curent working dir in which to place upload
 *            RecursionDepth = Internally used to keep track of the depth of recursion. Does not need to be specified.
 * Called by: DirList
 *            DirDelRecursive
 * Calls    : DirDelRecursive
 * Pre      : -
 * Post     : The dir and all files/dirs it contained are deleted (if possible)
 *            if an error occured, it will be shown in a javascript dialog.
 */
function DirDelRecursive ($FtpStream, $RemoteDir, $RecursionDepth = 0) {
	$DirContents = @ftp_nlist($FtpStream, $RemoteDir);
	if (is_array($DirContents)) {
		foreach ($DirContents as $Sub) {
			if (ftp_isdir($FtpStream, $Sub)) {
				$ErrorFiles .= DirDelRecursive ($FtpStream, $Sub, $RecursionDepth+1);
				if (@ftp_rmdir ($FtpStream, $Sub) == FALSE) {
					$ErrorFiles .= $Sub."\\n";
				}
			} else {
				// Del file
				if (@ftp_delete ($FtpStream, $Sub) == FALSE) {
					$ErrorFiles .= $Sub."\\n";
				}
			}
		}
	}

	if ($RecursionDepth == 0) {
		if (@ftp_rmdir ($FtpStream, $RemoteDir) == FALSE) {
			$ErrorFiles .= $RemoteDir."\\n";
		}

		if ($ErrorFiles != "") {
			?>
			<script>
				alert ("The following files and/or directories could not be removed: \n<?=$ErrorFiles?> Check permissions.");
			</script>
			<?
		}
	} else {
		return ($ErrorFiles);
	}
}

/* Deletes a remote dir on the ftp server
 *
 * Params   : FtpStream = An open stream to an ftp server
 *            Hostname = The hostname of the ftp server 
 *            RemoteDir = The remote curent working dir in which to place upload
 *            RemoteFile = Path and dirname of dir to delete
 * Called by: DirList
 * Calls    : DirList
 * Pre      : The dir must be empty to be deleted
 * Post     : The directory is removed or an error dialog is shown
 */
function DirDel($FtpStream, $RemoteFile) {
	$result = @ftp_rmdir ($FtpStream, $RemoteFile);
	
	?>
	<html>
	<head>
	</head>
	<body>
		<?
			if ($result === FALSE) {
				Error ("Couldn't delete directory ".$RemoteFile, ERR_NONE);
			}
		?>
	</body>
	</html>
	<?
}

/* Checks if a path+(file/dir) is a directory or not.
 *
 * Params   : FtpStream = An open stream to an ftp server
 *            Dir = The path to check
 * Called by: DirList
 * Calls    : -
 * Pre      : A connection to the ftp server must be open
 * Post     : Dir is check if it's a dir or file.
 * Returns  : true : Dir is a dir
 *            false : Dir is not a dir
 */
function ftp_isdir($FtpStream, $Dir) {
	if (!($CurrentDir = @ftp_pwd($FtpStream))) {
		Error ("Couldn't get the current working directory.", ERR_NONE);
	}
	if (@ftp_chdir($FtpStream, $Dir)) {
		$IsDir = true;
	} else {
		$IsDir = false;
	}

	@ftp_chdir($FtpStream, $CurrentDir);
	return $IsDir;
}

/* Read the bookmarks from the preferences directory.
 *
 * Params   : PreferenceDir = Directory on this server where preference files are kept.
 *            Hostname = Current host to which we are connecting
 *            Username = The username with which we are logging in
 * Called by: Main program
 * Calls    : -
 * Pre      : -
 * Post     : Preferences for this Hostname and Username combination are read.
 * Returns  : Array with strings which are bookmarks
 */
function PrefsLoadBookmarks ($PreferenceDir, $Hostname, $Username) {
	if ($Bookmarks = @file($PreferenceDir.$Hostname." ".$Username)) {
		for ($i = 0; $i < count($Bookmarks); $i++) {
			$Bookmarks[$i] = chop($Bookmarks[$i]);
		}
		return ($Bookmarks);
	} else {
		return (null);
	}
}
function PrefsSaveBookmarks ($PreferenceDir, $Hostname, $Username, $Bookmarks) {
	if (!$FileBookmarks = @fopen($PreferenceDir.$Hostname." ".$Username, "w")) {
		Error ("No permission to create a bookmarks file.\nYour bookmarks will not be saved.\mPlease contact the administrator of this site", ERR_NONE);
		$Bookmarks = "";
	} else {
		for ($i = 0; $i < count($Bookmarks); $i++) {
			fputs ($FileBookmarks, $Bookmarks[$i]."\n");
		}
		fclose ($FileBookmarks);
	}
}

function BookmarkNew($FtpStream, $PreferenceDir, $Hostname, $Username, &$Bookmarks, $RemoteDir) {
	if ($Bookmarks) {
		array_push ($Bookmarks, $RemoteDir);
	} else {
		$Bookmarks[0] = $RemoteDir;
	}
	PrefsSaveBookmarks ($PreferenceDir, $Hostname, $Username, $Bookmarks);
}

function BookmarkDel($FtpStream, $PreferenceDir, $Hostname, $Username, &$Bookmarks, $RemoteDir) {
	for ($i = 0; $i < count($Bookmarks); $i++) {
		if ($Bookmarks[$i] == $RemoteDir) {
			array_splice($Bookmarks, $i, 1);
		}
	}
	PrefsSaveBookmarks ($PreferenceDir, $Hostname, $Username, $Bookmarks);
}

function Help ($Username, $Hostname, $RemoteDir, $Bookmarks) {
	HtmlHeader ("Help");
	UserInterfaceHeader ($Username, $Hostname, $RemoteDir, $Bookmarks, 0);
	?>
		&nbsp; <a href="wtp.php?Action=DirList&RemoteDir=<?=$RemoteDir?>">Back</a>
		<center>
		<table width="95%">
		<tr><td>
		
		<h1>WTP Help</h1>
		<h2>About WTP</h2>
		<blockquote>
			WTP is a webbased (php) ftp client.<br><br>

			Current features:<br><br>

		    <ul>
			<li>Directory listing with Rights, Owner, Group, Size, Date, Filename, Symlinks to and file operations
		    <li>Creating new directories.
		    <li>Uploading/Downloading files
		    <li>Bookmarks
		    <li>Delete and move (multiple) files/directories
		    <li>Restriction to one/multiple hostname(s) or no restriction at all
			</ul><br>
			It currently only supports unix ftp servers, but support for windows ftp servers is planned. For a full list of confirmed compatibility with certain FTP servers, check the README.
			
			<table cellpadding="5">
			<tr valign="top"><td><b>Author</b></td><td>:</td><td>Ferry Boender &lt;%%EMAIL&gt;</td></tr>
			<tr valign="top"><td><b>Copyright</b></td><td>:</td><td>WTP is &copy; 2002-2003 by Ferry Boender</td></tr>
			<tr valign="top"><td><b>License</b></td><td>:</td><td>WTP released under the <a href="http://www.gnu.org/copyleft/gpl.html">GNU General Public License</a><br><br>
			This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.<br><br>
			This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.<br><br>
			You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.<br><br>                                                                  
			For more information, see the COPYING file supplied with this program.
			</td></tr>
			<tr valign="top"><td><b>Homepage</b></td><td>:</td><td><a href="%%HOMEPAGE">WTP Homepage</a></td></tr>
			</table>
			

		</blockquote>
		<h2>Login screen</h2>
		<blockquote>
		<table cellpadding="5">
			<tr valign="top"><td><b><nobr>WTP vX.Y.Z</nobr></b></td><td>:</td><td>Link to WTP's homepage</td></tr>
			<tr valign="top"><td><b>Hostname</b></td><td>:</td><td>Here you can enter the domainname of the ftp server you wish to connect to. No 'ftp://' prefix is needed. If it is already prefilled, you can only connect to that hostname. There's no use in changing the entry in that case. </td></tr>
			<tr valign="top"><td><b>Username</b></td><td>:</td><td>Enter your username here. Case sensitive.</td></tr>
			<tr valign="top"><td><b>Password</b></td><td>:</td><td>Enter your password here.</td></tr>
		</table>
		</blockquote>
		<h2>Icon bar</h2>
		<blockquote>
		<table cellpadding="5">
			<tr valign="top"><td><b><img src="images/dirup.gif" border="0" alt="Dir Up"></b></td><td>Takes you to one higher directory.</td></tr>
			<tr valign="top"><td><b><img src="images/refresh.gif" border="0" alt="Refresh"></b></td><td>Refreshes the current listing.</td></tr>
			<tr valign="top"><td><b><img src="images/dirhidden.gif" border="0" alt="Show/Hide hidden files"></b></td><td>Toggles the display of hidden files.</td></tr>
			<tr valign="top"><td><b><img src="images/dirnew.gif" border="0" alt="Dir New"></b></td><td>Allows you to create a new directory in the current one. Just type in the name of the dir you wish to create, and click the button.</td></tr>
			<tr valign="top"><td><b><img src="images/dirhome.gif" border="0" alt="Dir Home"></b></td><td>Takes you to the default dir. This is the first dir you saw when you logged in.</td></tr>
			<tr valign="top"><td><b><img src="images/upload.gif" border="0" alt="Upload"></b></td><td>Presents you with a file upload dialog in which you can pick a file to upload to the current directory</td></tr>
			<tr valign="top"><td><b><img src="images/logout.gif" border="0" alt="Logout"></b></td><td>Logs you out.</td></tr>
			<tr valign="top"><td><b><img src="images/help.gif" border="0" alt="Help"></b></td><td>This help screen</td></tr>
			<tr valign="top"><td><b><img src="images/bookmarknew.gif" border="0" alt="Add bookmark"></b></td><td>Adds a bookmark for the current directory in the bookmarks-dropdown list. If the current directory is already in the bookmark dropdown list, this icon will be red (see next item), and if you click it, the bookmark will be removed.</td></tr>
			<tr valign="top"><td><b><img src="images/bookmarkdel.gif" border="0" alt="Delete bookmark"></b></td><td>Deletes a bookmark. This button is only shown if the current dir is already a bookmark.</td></tr>
		</table>
		</blockquote>
		<h2>File Column Headers</h2>
		<blockquote>
		By clicking on some of the headers, you can sort the list of files accordingly. Whenever you decide to do this, a little icon next to the header will tell you if the list is sorted ascending or descending. When clicking the header again, the list will be sorted ascending if it was sorted descending and vice versa. Directories will always be shown at the top of the list.
		</blockquote>
		
		<h2>File Operations</h2>
		<blockquote>
		<table cellpadding="5">
			<tr valign="top"><td><b><img src="images/dirdel.gif" border="0" alt="Dir Delete"></b></td><td>Deletes the directory. If the directory contains any files or other directories then those will be deleted too.</td></tr>
			<tr valign="top"><td><b><img src="images/filedel.gif" border="0" alt="File Delete"></b></td><td>Deletes the file.</td></tr>
			<tr valign="top"><td><b><img src="images/dirmove.gif" border="0" alt="Dir Move"></b></td><td>Move or rename the directory. See Moving/Renaming for more info.</td></tr>
			<tr valign="top"><td><b><img src="images/filemove.gif" border="0" alt="File Move"></b></td><td>Move or rename the file. See Moving/Renaming for more info.</td></tr>
			<tr valign="top"><td><b>Delete</b></td><td>Clicking the button will delete all selected files.</td></tr>
			<tr valign="top"><td><b>Move</b></td><td>Clicking the button will allow you to move all selected files.</td></tr>
		</table>
		</blockquote>
		
		<h2>Moving/Renaming</h2>
		<blockquote>
			Whenever you move or rename one of more file(s), you will be presented with a input field in which you must enter the destination path for the file(s). The destination may be either relative to the current working directory ('../newname'), or absolute ('/home/john/newname'). When moving file(s) to another directory, you must make sure to include the trailing backslash ('/') on the destination path, or else wtp won't know you want to move it to another dir.
		</blockquote>
		</td></tr>
		</table>
		</center>
		<br><br>
	<?
}
