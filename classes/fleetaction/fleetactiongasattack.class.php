<?PHP

	class FleetActionGasAttack extends FleetAction
	{

		function FleetActionGasAttack()
		{
			$this->code = "gasattack";
			$this->name = "Gasangriff";
			$this->desc = "Greift das Ziel an und vernichtet Nahrung.";
			$this->longDesc = "Diese F�higkeit erm�glicht dem Angreiffer bei Gelingen der Aktion, Nahrung eines Planeten zu vernichten. Die Schadensh�he wird zuf�llig entschieden. Einsetzbar, wenn man dem Gegner nach gewonnenem Kampf noch die restliche Nahrung vernichten will.
Die Chance einen erfolgreichen Gas-Angriff durchzuf�hren erh�ht sich, in dem man die Giftgas-Technologie weiter erforscht!";
			$this->visible = true;
			$this->exclusive = false;					
			$this->attitude = 3;
			
			$this->allowPlayerEntities = true;
			$this->allowOwnEntities = false;
			$this->allowNpcEntities = false;
			$this->allowSourceEntity = false;
			$this->allowAllianceEntities = false;
		}

		function startAction() {} 
		function cancelAction() {}		
		function targetAction() {} 
		function returningAction() {}		
		
	}

?>