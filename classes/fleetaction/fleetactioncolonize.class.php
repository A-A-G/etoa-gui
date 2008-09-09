<?PHP

	class FleetActionColonize extends FleetAction
	{

		function FleetActionColonize()
		{
			$this->code = "colonize";
			$this->name = "Kolonialisieren";
			$this->desc = "Eine Basis auf dem Ziel errichten";
			$this->longDesc = "Am Anfang jeder Spielerkarriere hat man einen Planet zum Verwalten. Im ganzen Universum hat es jedoch noch unz�hlige andere Planeten, die unbewohnt sind. Um dies zu �ndern gibt es spezielle Schiffe, welche diese freien Planeten besiedeln k�nnen.
Ein solches Schiff kann meist nicht grosse Mengen an Ressourcen mitnehmen, aber f�r diesen Zweck hat man die M�glichkeit andere Schiffe mitzuschicken.
Es ist zu beachten, dass man maximal 15 Planeten kontrollieren kann! Bei einer erfolgreichen Kolonialisierung wird das Besiedlungsschiff verbraucht.";
			$this->visible = true;
			$this->exclusive = false;					
			$this->attitude = 1;
			
			$this->allowPlayerEntities = false;
			$this->allowOwnEntities = false;
			$this->allowNpcEntities = true;
			$this->allowSourceEntity = false;
		}

		function startAction() {} 
		function cancelAction() {}		
		function targetAction() {} 
		function returningAction() {}		
		
	}

?>