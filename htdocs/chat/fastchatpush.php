<?PHP
	/* fastchatpush von river */

	define('RELATIVE_ROOT','../');
	include_once(RELATIVE_ROOT.'inc/bootstrap.inc.php');
	include_once(RELATIVE_ROOT.'inc/encoding.inc.php');

	$s = $_SESSION;
	function kickChatUser($uid,$msg = '') 
	{
		if($msg == '')
		{
			$msg = 'Kicked by Admin';
		}
		$res = dbquery('
		UPDATE
			chat_users
		SET
			kick="'.mysql_real_escape_string($msg).'"
		WHERE
			user_id="'.$uid.'"');
		if (mysql_affected_rows()>0)
		{
			return true;
		}
		return false;
	}
	
	if (isset($s['user_id']))
	{
		$admin = 0;
		$res = dbquery('
		SELECT
			user_chatadmin,admin
		FROM
			users
		WHERE
			user_id='.$s['user_id'].';');
		if (mysql_num_rows($res)>0) // Should always be true, otherwise the user does not exist
		{
			// chatadmins = 2, admins = 1, noadmin-entwickler = 3,
			// leiter team community = 4, admin-entwickler = 5
			$arr = mysql_fetch_assoc($res);
			if($arr['admin'] == 1)
			{
				if($arr['user_chatadmin'] == 3)
				{
					$admin = 5; // Entwickler mit Adminrechten
				}
				else
				{
					$admin = 1; // Admin
				}
			}
			elseif ($arr['user_chatadmin'] == 1)
				$admin = 2; // Chatadmin
			elseif ($arr['user_chatadmin'] == 2)
				$admin = 4; // Leiter Team Community
			elseif($arr['admin'] == 2)
				$admin = 3; // Entwickler ohne Adminrechte
		}
		else
		{
			die('nu'); // no user
		}

		$ct = $_POST['ctext'];
		$isCommand = false;

		if ( $admin > 0 && $admin != 3) // Keine Kommandos für Nichtadmin-Entwickler
		{
			$m = array();
			$words = StringUtils::splitBySpaces($ct);
			$commandMatch = array();
			if (count($words) > 0 && preg_match('#^/([a-z]+)$#i', array_shift($words), $commandMatch)) 
			{
			  $command = strtolower($commandMatch[1]);
			  $isCommand = true;
			  
			  // Kick user
			  if ($command == "kick")
			  {
				if (isset($words[0])) 
				{
				  $uid = User::findIdByNick($words[0]);
				  if ($uid>0)
				  {
					$msg = (count($words) > 1) ? implode(' ', array_slice($words, 1)) : '';
					if (kickChatUser($uid, $msg))
					{
					   chatSystemMessage($words[0].' wurde gekickt!'.($msg != '' ? ' Grund: '.$msg : ''));
					}
					else
					{
					  die('aa:User is not online in chat!');
					}
				  }
				  else
				  {
					die('aa:A user with this nick does not exist!');
				  }
				}
				else
				{
				  die('aa:No user specified!');
				}          
			  }
			  
			  // Ban user
			  elseif ($command == "ban")
			  {
				if (isset($words[0])) 
				{
				  $uid = User::findIdByNick($words[0]);
				  if ($uid>0)
				  {
					$text = (count($words) > 1) ? implode(' ', array_slice($words, 1)) : '';
					dbquery('INSERT INTO
					  chat_banns
						(user_id,reason,timestamp)
					  VALUES ('.$uid.',"'.mysql_real_escape_string($text).'",'.time().')
					  ON DUPLICATE KEY UPDATE
						timestamp='.time().',reason="'.mysql_real_escape_string($text).'"');
					kickChatUser($uid, $text);
					chatSystemMessage($words[0].' wurde gebannt! Grund: '.$text);
				  }
				  else
				  {
					die('aa:A user with this nick does not exist!');
				  }
				}
				else
				{
				  die('aa:No user specified!');
				}          
			  }
	  
			  elseif ($command == "unban")
			  {
				if (isset($words[0])) 
				{
				  $uid = User::findIdByNick($words[0]);
				  if ($uid>0)
				  {
					dbquery('DELETE FROM
					  chat_banns
					WHERE
					  user_id='.$uid.';');
					if (mysql_affected_rows()>0)
					{
					  die('aa:Unbanned '.$words[0].'!');
					}
					else
					{
					  die('aa:A user with that nick is not banned!');
					}
				  }
				  else
				  {
					die('aa:A user with this nick does not exist!');
				  }
				}
				else
				{
				  die('aa:No user specified!');
				}
			  }
	  
			  elseif ($command == "banlist")
			  {
				$res = dbquery('SELECT
				  user_id,reason,timestamp
				FROM
				  chat_banns
				;');
				if (mysql_num_rows($res)>0)
				{
				  $out='';
				  while ($arr=mysql_fetch_assoc($res))
				  {
					$tu = new User($arr['user_id']);
					if ($tu->isValid)
					{
					  $out.= $tu->nick.': '.$arr['reason'].' ('.df($arr['timestamp']).")\n";
					}
				  }
				  die('bl:'.$out);
				}
				else
				{
				  die('aa:Bannliste leer!');
				}
			  }        
			  
			  // Unknown command
			  else
			  {
				die('aa:Unknown command \''.$command.'\'!');
			  }        
			}
		}
		if(!$isCommand)
		{
			$hash = md5($ct);
			// Woo Hoo, Md5 hashtable
			if ($ct!='' && (!isset($_SESSION['lastchatmsg']) || $_SESSION['lastchatmsg']!= $hash) )
			{
				dbquery("INSERT INTO
					chat
				(
					timestamp,
					nick,
					text,
					color,
					user_id,
					admin
				)
				VALUES
				(
					".time().",
					'".$s['user_nick']."',
					'".mysql_real_escape_string(($ct))."',
					'".(isset($_SESSION['ccolor'])?('#'.$_SESSION['ccolor']):'')."',
					'".$s['user_id']."',
					'".$admin."'
				);");
				dbquery("INSERT INTO
					chat_log
				(
					timestamp,
					nick,
					text,
					color,
					user_id,
					admin
				)
				VALUES
				(
					".time().",
					'".$s['user_nick']."',
					'".mysql_real_escape_string(($ct))."',
					'".(isset($_SESSION['ccolor'])?('#'.$_SESSION['ccolor']):'')."',
					'".$s['user_id']."',
					'".$admin."'
				);");			
				$_SESSION['lastchatmsg']=$hash;
			}
			else
			{
				die('de'); // zweimal gleiche Nachricht nacheinander
			}
		}
	}
	else
	{
		die('nl'); // !isset $s[userid] => not logged in
	}

?>