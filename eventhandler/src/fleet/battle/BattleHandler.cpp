
#include "BattleHandler.h"

#include "../../objects/Fleet.h"
#include "../../entity/Entity.h"
#include "../../objects/Log.h"
#include "../../MysqlHandler.h"
#include "../../config/ConfigHandler.h"
#include "../../util/Functions.h"
#include "../../reports/BattleReport.h"

#define MYSQLPP_MYSQL_HEADERS_BURIED
#include <mysql++/mysql++.h>

#include <ctime>
#include <cmath>
#include <cstdio>

void BattleHandler::battle(Fleet* fleet, Entity* entity, Log* log, bool ratingEffect)
{
  Config &config = Config::instance();
  BattleReport report(fleet->getUserId(), entity->getUserId(), fleet->getEntityTo(), fleet->getEntityFrom(), fleet->getLandtime(), fleet->getId());
  std::string users = fleet->getUserIds();
  report.setUser(users);
  size_t found=users.find_first_of(",");
  int attCnt = -1;
  while (found!=std::string::npos) 
  {
    report.addUser(getUser(users, found));
    attCnt++;
  }
  users = entity->getUserIds();
  report.setEntityUser(users);
  found=users.find_first_of(",");
  int defCnt = -1;
  while (found!=std::string::npos) 
  {
    report.addUser(getUser(users, found));
    defCnt++;
  }
  // Kampf abbrechen falls User gleich 0
  if (entity->getUserId()==0
          || (fleet->getLeaderId() > 0 && (fleet->fleetUser->getAllianceId()==entity->getUser()->getAllianceId() && fleet->fleetUser->getAllianceId()!=0))
          || (fleet->getLeaderId() == 0 && fleet->getUserId()==entity->getUserId())) 
  {
    report.setSubtype("battlefailed");
    report.setResult(0);
    log->addText("Action failed: Opponent error");
  }
  // Kampf abbrechen und Flotte zum Startplanet schicken wenn Kampfsperre aktiv ist
  else if (((int)config.nget("battleban",0) != 0) 
          && ((int)config.nget("battleban_time",1) <= std::time(0))
          && ((int)config.nget("battleban_time",2) > std::time(0))) 
  {
    report.setSubtype("battleban");
    report.setResult(0);
    log->addText("Action failed: Battleban error");
  }
  else 
  {
    report.setSubtype("battle");
   
    //Kampf Daten
    //init... = wert vor dem kampf (wird nicht verändert) und c... aktueller Wert
    double initAttWeapon = fleet->getWeapon(true);
    double initDefWeapon = entity->getWeapon(true);
    double initAttStructure = fleet->getStructure(true);
    double initDefStructure = entity->getStructure(true);
    double initAttShield = fleet->getShield(true);
    double initDefShield = entity->getShield(true);
    double initAttStructureShield = fleet->getStructShield(true);
    double initDefStructureShield = entity->getStructShield(true);
    double cAttStructureShield = initAttStructureShield;
    double cDefStructureShield = initDefStructureShield;
    
    //Report
    report.setShield(initAttShield);
    report.setStructure(initAttStructure);
    report.setWeaponTech(fleet->getWeaponTech());
    report.setShieldTech(fleet->getShieldTech());
    report.setStructureTech(fleet->getStructureTech());
    
    report.setEntityShield(initDefShield);
    report.setEntityStructure(initDefStructure);
    report.setEntityWeaponTech(entity->getWeaponTech());
    report.setEntityShieldTech(entity->getShieldTech());
    report.setEntityStructureTech(entity->getStructureTech());
    
    report.setShips(fleet->getShipString());
    report.setEntityShips(entity->getShipString());
    report.setEntityDef(entity->getDefString());

    //
    //Der Kampf!
    //
    for (int bx = 1; bx <= config.nget("battle_rounds",0); bx++) {
      report.setRounds(bx);
      report.setWeapon(fleet->getWeapon(true));
      report.setCount(fleet->getCount(true));
      report.setEntityWeapon(entity->getWeapon(true));
      report.setEntityCount(entity->getCount(true));

      double FleetAtt = fleet->getWeapon(true);
      double EntityAtt = entity ->getWeapon(true);
      
      cAttStructureShield -= entity->getWeapon(true);
      cDefStructureShield -= fleet->getWeapon(true);

      cAttStructureShield = std::max(0.0,cAttStructureShield);
      cDefStructureShield = std::max(0.0,cDefStructureShield);

      double attPercent;
      if (entity->getWeapon(true) == 0 && initAttStructureShield==cAttStructureShield) 
      {
        attPercent = 1;
      }
      else if (cAttStructureShield==0) 
      {
        attPercent = 0;
      }
      else 
      {
        attPercent = cAttStructureShield/initAttStructureShield;
      }

      double defPercent;
      if (fleet->getWeapon(true) == 0 && initDefStructureShield==cDefStructureShield) 
      {
        defPercent = 1;
      }
      else if (cDefStructureShield==0) 
      {
        defPercent = 0;
      }
      else 
      {
        defPercent = cDefStructureShield/initDefStructureShield;
      }

      fleet->setPercentSurvive(attPercent,true);
      entity->setPercentSurvive(defPercent,true);

      // Heal
      double fleetheal = fleet->getHeal(true);
      double entityheal = entity->getHeal(true);
      // Restrict healing to maximal max_heal% of the damage received
      if (fleetheal > (double)config.nget("max_heal",0)*EntityAtt) 
      {
          fleetheal = (double)config.nget("max_heal",0)*EntityAtt;
      }
      if (entityheal > (double)config.nget("max_heal",0)*FleetAtt) 
      {
          entityheal = (double)config.nget("max_heal",0)*FleetAtt;
      }
      
      report.setHeal(fleetheal);   
      report.setEntityHeal(entityheal);

      if (fleetheal > 0) 
      {
        cAttStructureShield += fleetheal;
        if (cAttStructureShield > initAttStructureShield)
        {
          cAttStructureShield = initAttStructureShield;
        }
        fleet->setPercentSurvive(cAttStructureShield/initAttStructureShield,true);
      }
      if (entityheal > 0) 
      {
        cDefStructureShield += entityheal;
        if (cDefStructureShield > initDefStructureShield)
        {
          cDefStructureShield = initDefStructureShield;
        }
        entity->setPercentSurvive(cDefStructureShield/initDefStructureShield,true);
      }

      if (fleet->getCount(true) <= 0 || entity->getCount(true) <= 0)
      {
        break;
      }
    }

    //
    //Daten nach dem Kampf
    //

    //
    //überlebende Schiffe errechnen
    //

    //Erfahrung für die Spezialschiffe errechnen
    fleet->addExp(entity->getExp() / (100000.0*attCnt));
    entity->addExp(fleet->getExp() / (100000.0*defCnt));
    report.setExp(fleet->getAddedExp());
    report.setEntityExp(entity->getAddedExp());

    //Das entstandene Trümmerfeld erstellen/hochladen
    entity->addWfMetal(fleet->getWfMetal(true) + entity->getObjectWfMetal(true));
    entity->addWfCrystal(fleet->getWfCrystal(true) + entity->getObjectWfCrystal(true));
    entity->addWfPlastic(fleet->getWfPlastic(true) + entity->getObjectWfPlastic(true));

    //
    //Der Angreifer hat gewonnen!
    //
    std::vector<double> raid(5, 0);
    if ((entity->getCount(true) == 0) && (fleet->getCount(true) > 0)) 
    {
      _battleResult = 1;
      double percent = std::min(fleet->getBountyBonus(),(fleet->getCapacity(true) / entity->getResSum()));
      raid[0] = entity->removeResMetal(fleet->addMetal(entity->getResMetal(percent),true));
      raid[1] = entity->removeResCrystal(fleet->addCrystal(entity->getResCrystal(percent),true));
      raid[2] = entity->removeResPlastic(fleet->addPlastic(entity->getResPlastic(percent),true));
      raid[3] = entity->removeResFuel(fleet->addFuel(entity->getResFuel(percent),true));
      raid[4] = entity->removeResFood(fleet->addFood(entity->getResFood(percent),true));
      report.setRes(raid[0], raid[1], raid[2], raid[3], raid[4], 0);
    }
    //
    //Der Verteidiger hat gewonnen
    //
    else if (fleet->getCount(true)==0 && entity->getCount(true)>0)
    {
      _battleResult = 2;
    }
    //
    //Der Kampf endete unentschieden
    //
    else 
    {
      //
      //	Unentschieden, beide Flotten wurden zerstört
      //
      if ((fleet->getCount(true) == 0) && (entity->getCount(true) == 0))
      {
        _battleResult = 3;
      }
      //
      //	Unentschieden, beide Flotten haben überlebt
      //
      else
      {
        _battleResult = 4;
      }
    }
    report.setResult(_battleResult);

    report.setWf(entity->getAddedWfMetal(), entity->getAddedWfCrystal(), entity->getAddedWfPlastic());

    //
    //Auswertung
    //
    report.setShipsEnd(fleet->getShipString());
    report.setEntityShipsEnd(entity->getShipString(true,true));
    report.setEntityDefEnd(entity->getDefString(true));
    report.setRestore(round((config.nget("def_restore_percent",0) + entity->getUser()->getSpecialist()->getSpecialistDefRepair() - 1)*100));
    report.setRestoreCivilShips(round(config.nget("civil_ship_restore_percent",0)*100));
    
    // Prüft, ob Krieg herrscht
    bool alliancesHaveWar = false;
    if(entity->getUser() != NULL)
    {
      alliancesHaveWar = fleet->fleetUser->isAtWarWith(entity->getUser()->getAllianceId());
    } 

    //Log schreiben
    My &my = My::instance();
    mysqlpp::Connection *con_ = my.get();
    mysqlpp::Query query = con_->query();
    query << "INSERT DELAYED INTO "
    << "	logs_battle_queue "
    << "("
    << "	fleet_id, "
    << "	user_id, "
    << "	entity_user_id, "
    << "	user_alliance_id, "
    << "	entity_user_alliance_id, "
    << "	war, "
    << "	entity_id, "
    << "	action, "
    << "	result, "
    << "	fleet_ships_cnt, "
    << "	entity_ships_cnt, "
    << "	entity_defs_cnt, "
    << "	fleet_weapon, "
    << "	fleet_shield, "
    << "	fleet_structure, "
    << "	fleet_weapon_bonus, "
    << "	fleet_shield_bonus, "
    << "	fleet_structure_bonus, "
    << "	entity_weapon, "
    << "	entity_shield, "
    << "	entity_structure, "
    << "	entity_weapon_bonus, "
    << "	entity_shield_bonus, "
    << "	entity_structure_bonus, "
    << "	fleet_win_exp, "
    << "	entity_win_exp, "
    << "	win_metal, "
    << "	win_crystal, "
    << "	win_pvc, "
    << "	win_tritium, "
    << "	win_food, "
    << "	tf_metal, "
    << "	tf_crystal, "
    << "	tf_pvc, "
    << "	timestamp, "
    << "	landtime "
    << ")"
    << "VALUES"
    << "("
    << "	" << fleet->getId() << ", "
    << "	" << mysqlpp::quote << fleet->getUserIds() << ", "
    << "	" << mysqlpp::quote << entity->getUserIds() << ", "
    << "	" << fleet->fleetUser->getAllianceId() << ", "
    << "	" << entity->getUser()->getAllianceId() << ", "
    << "	" << alliancesHaveWar << ", "
    << "	" << entity->getId() << ", "
    << "	" << mysqlpp::quote << fleet->getAction() << ", "
    << "	" << _battleResult << ", "
    << "	" << fleet->getInitCount(true) << ", "
    << "	" << entity->getInitCount(true) - entity->getInitDefCount() << ", "
    << "	" << entity->getInitDefCount() << ", "
    << "	" << initAttWeapon << ", "
    << "	" << initAttShield << ", "
    << "	" << initAttStructure << ", "
    << "	" << fleet->getWeaponBonus() * 100 << ", "
    << "	" << fleet->getShieldBonus() * 100 << ", "
    << "	" << fleet->getStructureBonus() * 100 << ", "
    << "	" << initDefWeapon << ", "
    << "	" << initDefShield <<", "
    << "	" << initDefStructure << ", "
    << "	" << entity->getWeaponBonus() * 100 << ", "
    << "	" << entity->getShieldBonus() * 100 << ", "
    << "	" << entity->getStructureBonus() * 100 << ", "
    << "	" << fleet->getAddedExp() << ", "
    << "	" << entity->getAddedExp() << ", "
    << "	" << raid[0] << ", "
    << "	" << raid[1] << ", "
    << "	" << raid[2] << ", "
    << "	" << raid[3] << ", "
    << "	" << raid[4] << ", "
    << "	" << entity->getAddedWfMetal() << ", "
    << "	" << entity->getAddedWfCrystal() << ", "
    << "	" << entity->getAddedWfPlastic() << ", "
    << "	" << std::time(0) << ", "
    << "	" << fleet->getLandtime() << ");";
    query.store();

    std::cerr << query.str();
    log->addText(("Battle id: " + etoa::d2s(query.insert_id())));

    if (initAttWeapon>0 && ratingEffect) 
    {
      //Battlepoints
      std::string attReason = "Angriff gegen" + entity->getUserNicks();
      std::string defReason = "Verteidigung gegen" + fleet->getUserNicks();
      int attPoints = 0, defPoints = 0;
      short attResult = 0, defResult = 0;
      switch (_battleResult)
      {
        case 1:	//Angreifer hat gewonnen
        {
          attPoints = 3;
          attResult = 2;
          break;
        }
        case 2:	//Angreifer hat verloren
        {
          _returnFleet = false;
          attPoints = 1;
          defPoints = 2;
          defResult = 2;
          break;
        }
        case 3:	//beide flotten sind kaputt
        {
          _returnFleet = false;
          attPoints = 1;
          defPoints = 1;
          attResult = 1;
          defResult = 1;
          break;
        }
        case 4: //beide flotten haben überlebt
        {
          attPoints = 1;
          defPoints = 1;
          attResult = 1;
          defResult = 1;
          break;
        }
      }
      std::string users = fleet->getUserIds();
      found=users.find_first_of(",");
      while (found!=std::string::npos) 
      {
        etoa::addBattlePoints(getUser(users,found), attPoints, attResult, attReason);
      }
      users = entity->getUserIds();
      found=users.find_first_of(",");
      while (found!=std::string::npos) 
      {
        etoa::addBattlePoints(getUser(users,found), defPoints, defResult, attReason);
      }
      // elorating if 1 vs 1
      if ((attCnt == 1) && (defCnt == 1))
      {
        int attElo = fleet->fleetUser->getElorating();
        int defElo = entity->getUser()->getElorating();
        double winPercentage =  1/(1 + pow(10, (defElo - attElo)/400.0));
        entity->getUser()->addElorating(defElo + (int)config.nget("elorating", 1) * ((defResult/2) - 1 + winPercentage));
        fleet->fleetUser->addElorating(attElo + (int)config.nget("elorating", 1) * ((attResult/2) - winPercentage));
      }
    }
    fleet->addRaidedRes();
  }
}

int BattleHandler::getUser(std::string& users, size_t& found) const
{
  users = users.substr(found+1);
  found = users.find_first_of(",");
  return (int)etoa::s2d(users.substr(0,found));
}

bool BattleHandler::getReturnFleet() const
{
  return _returnFleet;
}

bool BattleHandler::getBattleResult() const
{
  return _battleResult;
}

