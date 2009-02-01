<?PHP
	function update_day()
	{
		global $conf;

	 	// Inaktive User l�schen
		$tmr = timerStart();
		$ui = Users::removeInactive();
		$ud = Users::removeDeleted();
		$log = "Inaktive und als gel�scht markierte User gel�scht.\nDauer: ".timerStop($tmr)." sec\n\n";

		// Alte Benuterpunkte-Logs l�schen
		$tmr = timerStart();
		$nr = Users::cleanUpPoints();
		$log.= "$nr alte Userpunkte-Logs gel�scht.\nDauer: ".timerStop($tmr)." sec\n\n";

		// Alte Session-Logs
		$tmr = timerStart();
		$nr = Users::cleanUpSessionLogs();
		$log.= "$nr alte Session-Logs gel�scht.\nDauer: ".timerStop($tmr)." sec\n\n";
		
		// Alte Logs l�schen
		$tmr = timerStart();
		$nr = Log::removeOld();
		$log.= "$nr alte Logs gel�scht.\nDauer: ".timerStop($tmr)." sec\n\n";

		// Alte Nachrichten l�schen
		$tmr = timerStart();
		Message::removeOld();
		$log.= "Alte Nachrichten gel�scht.\nDauer: ".timerStop($tmr)." sec\n\n";

		// Abgelaufene Sperren l�schen
		$tmr = timerStart();
		Users::removeOldBanns();
		$log.= "Abgelaufene Sperren gel�scht.\nDauer: ".timerStop($tmr)." sec\n\n";

		// Tabellen optimieren
		$tmr = timerStart();
		optimize_tables();
		$log.= "Tabellen optimiert.\nDauer: ".timerStop($tmr)." sec\n\n";

		// Remove old ip-hostname combos from cache
		$res = dbquery("
		DELETE FROM
			hostname_cache
		WHERE
			timestamp<".(time()-86400)."
		");

		return $log;
	}


	function update_hour()
	{
		global $conf;

		// Punkteberechnung
		$tmr = timerStart();
		Ranking::calc();
		if (ENABLE_USERTITLES==1)
		{
			Ranking::calcTitles();
		}
		$log = "\nPunkte aktualisiert.\nDauer: ".timerStop($tmr)." sec\n\n";

		// Wurml�cher vertauschen
		//$tmr = timerStart();
		//Wormhole::randomize();
		//$log.= "Wurml&ouml;cher vertauscht.\nDauer: ".timerStop($tmr)." sec\n\n";

		// Closes all open tables, forces all tables in use to be closed, and flushes the query cache.
		dbquery("FLUSH TABLES");

		return $log;
	}


	function update_30minute()
	{
		global $conf;

		return $log;
	}


	function update_5minute()
	{
		global $conf;

		// User Statistik speichern
		$rres=dbquery("SELECT COUNT(user_id) FROM users;");
		$rarr=mysql_fetch_row($rres);
		$gres=dbquery("SELECT COUNT(user_id) FROM users WHERE user_acttime>".(time()-$conf['user_timeout']['v']).";");
		$garr=mysql_fetch_row($gres);
		dbquery("INSERT INTO user_onlinestats (stats_timestamp,stats_count,stats_regcount) VALUES (".time().",".$garr[0].",".$rarr[0].");");
		$log = "\nUser-Statistik: ".$garr[0]." User online, ".$rarr[0]." User registriert\n\n";

		
		// Krieg-Frieden-Update
		$tmr = timerStart();
		$nr = warpeace_update();
		$log.= "$nr Krieg und Frieden aktualisiert.\nDauer: ".timerStop($tmr)." sec\n\n";		
		
		// Chat-Cleanup
		$res = dbquery("SELECT id FROM chat ORDER BY id DESC LIMIT 200,1");
		if (mysql_num_rows($res)>0)
		{
			$arr=mysql_fetch_row($res);
			dbquery("DELETE FROM chat WHERE id < ".$arr[0]);		
		}

		// Userstats
		UserStats::generateImage(GAME_ROOT_DIR."/".USERSTATS_OUTFILE);
		UserStats::generateXml(GAME_ROOT_DIR."/".XML_INFO_FILE);

		return $log;
	}

	function update_minute()
	{
		global $conf;

		// Zufalls-Event ausl�sen
		//PlanetEventHandler::doEvent(RANDOM_EVENTS_PER_UPDATE);

		$nr = warpeace_update();
		
		// Mailqueue abarbeiten
		$tmr = timerStart();
		$cnt = mail_queue_send($conf['mailqueue']['v']);
		$log = "Die E-Mail-Warteschlange wurde abgearbeitet, [b]".$cnt."[/b] Mails versendet!\nDauer: ".timerStop($tmr)." sec\n\n";

		// Flotten updaten
		$tmr = timerStart();

		check_missiles();
      
     /*  
    $fa = updateAllFleet();
    $log .= "Es wurden [b]".$fa[0]."[/b] Flotten aktualisiert!\n";
    $log .= "Dauer: ".timerStop($tmr)." sec\n";
    for ($i=0;$i<$fa[0];$i++)
    {
        $log .= "Flotte [b]".$fa[1][$i]."[/b]\n";
    }
		*/

		chatUserCleanUp();

		return $log;
	}
?>