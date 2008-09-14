<?PHP

	class FleetActionCollectCrystal extends FleetAction
	{

		function FleetActionCollectCrystal()
		{
			$this->code = "collectcrystal";
			$this->name = RES_CRYSTAL."staub sammeln";
			$this->desc = RES_CRYSTAL." von Sternennebeln sammeln";
			$this->longDesc = "Ebenso wie die Asteroiden und die Gasplaneten waren die Intergalaktischen Nebelfelder lange Zeit ein unerfoschtes Mysterium. Doch heute bezieht man auch aus ihnen einen Nutzen. Man hat herausgefunden, dass diese Nebelfelder eine extrem siliziumreiche Atmosph�re haben und so ist es nach ein paar Jahren intensiver Forschung gelungen, dieses Silizium zu bergen!
				Doch wie auch beim Asteroiden sammeln gibt es hier ein gewisses Gefahrenrisiko. Es ist schonmal vorgekommen, dass die starken Magnetfelder, welche das Nebelfeld ausstrahlt, die Bordelektronik der Schiffe lahmgelegt hat und diese dann im unentlichen Weltall verschollen geblieben sind!";
			$this->visible = false;
			$this->exclusive = false;					
			$this->attitude = 0;
			
			$this->allowPlayerEntities = false;
			$this->allowOwnEntities = false;
			$this->allowNpcEntities = true;
			$this->allowSourceEntity = false;
			$this->allowAllianceEntities = false;
		}

		function startAction() {} 
		function cancelAction() {}		
		function targetAction() {} 
		function returningAction() {}		
		
	}

?>