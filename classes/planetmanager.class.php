<?PHP


	//
	// Planetenklasse
	//
	// Diese Klasse beinhaltet alle Planeten des Spielers als Objekte (weiter oben definiert)
	//

	class PlanetManager
	{
		private $items;
		private $itemObjects;
		private $loaded;
		private $num;
		function PlanetManager(Array $i)
		{
			$this->items = $i;
			$this->loaded=false;
			$this->itemObjects = array();
			$this->num = count($i);
		}

		static function getFreePlanet($sx=0,$sy=0)
		{
			$cfg = Config::getInstance();
			$sql = "
				SELECT
					planets.id
				FROM
					cells
				INNER JOIN
				(
					entities
					INNER JOIN
					(
						planets 
						INNER JOIN
							planet_types 
							ON planet_type_id=type_id
							AND type_habitable=1
					)
					ON planets.id=entities.id
					AND planets.planet_fields>'".$cfg->value('user_min_fields')."'
					AND planets.planet_user_id='0'
 				)
				ON entities.cell_id=cells.id ";					
			if ($sx>0)
				$sql.=" AND cells.sx=".$sx." ";
			if ($sy>0)
				$sql.=" AND cells.sy=".$sy." ";
	
			$sql.="ORDER BY
					RAND()
			LIMIT 1";
			$tres = dbquery($sql);				
			if (mysql_num_rows($tres)==0)
			{
				return false;
			}
			$tarr = mysql_fetch_row($tres);			
			return $tarr[0];
		}

		public function prevId()
		{
			global $s;
			for ($x=0;$x<$this->num;$x++)
			{
				if ($this->items[$x]==$s['cpid'])
				{
					return $this->items[($x+$this->num-1)%$this->num];
				}
			}
			echo ($x-1)%$this->num;
		}

		public function nextId()
		{
			global $s;
			for ($x=0;$x<$this->num;$x++)
			{
				if ($this->items[$x]==$s['cpid'])
				{
					return $this->items[($x+1)%$this->num];
				}
			}
		}

		private function load()
		{
			if (!$this->loaded)
			{
				foreach ($this->items as $i)
				{
					$this->itemObjects[] = new Planet($i);
				}
				$this->loaded=true;
			}
		}

		function getSelectField()
		{
			global $s,$page;
			$this->load();
			ob_start();
			echo "<select name=\"nav_mode_select\" id=\"nav_mode_select\" onchange=\"changeNav(this.selectedIndex,'".$page."')\">";
			foreach ($this->itemObjects as $i)
			{
				echo "<option value=\"".$i->id()."\"";
				if ($s['cpid']==$i->id())
					echo " selected=\"selected\"";
				echo ">".$i."</option>\n";
			}
			echo "</select>";
			$str = ob_get_contents();
			ob_end_clean();			
			return $str;
		}		

		function getLinkList()
		{
			global $s,$page;
			$this->load();
			ob_start();			
			foreach ($this->itemObjects as $i)
			{
				if ($s['cpid']==$i->id())
					echo "<a href=\"?page=$page&amp;planet_id=".$i->id()."\"><b>".$i."</b></a>\n";
				else
					echo "<a href=\"?page=$page&amp;planet_id=".$i->id()."\">".$i."</a>\n";
			}
			$str = ob_get_contents();
			ob_end_clean();			
			return $str;			
		}
		
	
		

	}

?>
